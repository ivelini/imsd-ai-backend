<?php

namespace Tests\Feature\Admin\Geo;

use App\Jobs\GeoImport\PointImportJob;
use App\Models\Auth\Admin;
use App\Models\Auth\AdminRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use OpenSpout\Reader\XLSX\Reader;
use Tests\TestCase;

/** HTTP-слой импорта точек выдачи. */
class PointImportTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $role = AdminRole::create(['name' => 'Главный администратор', 'code' => 'super-admin']);
        $this->admin = Admin::create([
            'name' => 'Admin', 'email' => 'admin@test.ru',
            'password' => bcrypt('password'), 'admin_role_id' => $role->id, 'is_active' => true,
        ]);
    }

    public function test_requires_auth(): void
    {
        $this->postJson('/api/admin/geo/points/import')->assertUnauthorized();
    }

    public function test_upload_returns_202(): void
    {
        Bus::fake();
        Storage::fake('local');

        $file = UploadedFile::fake()->create('points.xlsx', 100);

        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/geo/points/import', ['file' => $file])
            ->assertStatus(202)
            ->assertJsonStructure(['data' => ['import_id']]);

        $this->assertDatabaseHas('product_imports', ['type' => 'point', 'status' => 'pending']);
    }

    public function test_parse_points_xlsx(): void
    {
        $path = base_path('documentations/import/points.xlsx');
        if (! file_exists($path)) {
            $this->markTestSkipped('Файл points.xlsx не найден.');
        }

        $reader = new Reader;
        $reader->open($path);

        $rows = 0;
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $rowIndex => $row) {
                if ($rowIndex > 1) {
                    $rows++;
                }
            }
        }
        $reader->close();

        $this->assertGreaterThan(0, $rows, 'XLSX не содержит данных');
    }

    public function test_import_row_creates_region_and_city(): void
    {
        $path = base_path('documentations/import/points.xlsx');
        if (! file_exists($path)) {
            $this->markTestSkipped('Файл points.xlsx не найден.');
        }

        $job = new PointImportJob(
            0,
            $path,
            config('point_import.column_map'),
            config('point_import.required_columns'),
            config('point_import.boolean_true'),
        );

        [$priceCols] = PointImportJob::detectColumnsFromHeaders([
            'code', 'region_name', 'city_name', '0-5000', '5001-8500',
        ]);

        $refPrice = new \ReflectionProperty($job, 'priceColumns');
        $refPrice->setValue($job, $priceCols);

        $ref = new \ReflectionMethod($job, 'importRow');

        $ref->invoke($job, [
            'region_code' => '99',
            'region_name' => 'Тестовый регион',
            'city_name' => 'Тестовый город',
            'delivery_days' => '3',
            'address' => 'ул. Тестовая, 1',
            'phone' => '+7 (999) 999-99-99',
            'pickup_from_truck_raw' => 'Да',
            '0-5000' => '100',
            '5001-8500' => '200',
        ]);

        $this->assertDatabaseHas('regions', ['code' => '99']);
        $this->assertDatabaseHas('cities', ['name' => 'Тестовый город']);
        $this->assertDatabaseHas('city_price_rules', ['price_from' => 0, 'price_to' => 5000, 'markup' => 100]);
        $this->assertDatabaseHas('delivery_points', ['address' => 'ул. Тестовая, 1']);
    }
}
