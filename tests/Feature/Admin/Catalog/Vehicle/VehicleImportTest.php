<?php

namespace Tests\Feature\Admin\Catalog\Vehicle;

use App\Enums\Import\ImportType;
use App\Models\Auth\Admin;
use App\Models\Auth\AdminRole;
use App\Models\System\ProductImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** HTTP-слой импорта характеристик автомобилей: загрузка CSV, авторизация, полный цикл. */
class VehicleImportTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $role = AdminRole::create([
            'name' => 'Главный администратор',
            'code' => 'super-admin',
        ]);

        $this->admin = Admin::create([
            'name' => 'Admin',
            'email' => 'admin@test.ru',
            'password' => bcrypt('password'),
            'admin_role_id' => $role->id,
            'is_active' => true,
        ]);
    }

    public function test_store_requires_authentication(): void
    {
        $this->postJson('/api/admin/catalog/import/vehicle')
            ->assertUnauthorized();
    }

    public function test_store_validates_file_required(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/catalog/import/vehicle', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_store_validates_csv_format(): void
    {
        $file = UploadedFile::fake()->create('test.xlsx', 100);

        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/catalog/import/vehicle', ['file' => $file])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_store_accepts_csv_and_returns_import_id(): void
    {
        Bus::fake();
        Storage::fake('local');

        $file = UploadedFile::fake()->create('vehicle.csv', 100);

        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/catalog/import/vehicle', ['file' => $file])
            ->assertStatus(202)
            ->assertJsonStructure(['data' => ['import_id']]);

        $this->assertDatabaseHas('product_imports', [
            'id' => 1,
            'type' => ImportType::Vehicle->value,
            'status' => 'pending',
        ]);
    }

    /** @group slow */
    public function test_import_creates_full_hierarchy(): void
    {
        Storage::fake('local');

        $csv = implode(';', [
            'Acura', 'CDX', 'CDX', '1.5 T', '2016',
            '235/50 R18', '235/45 R19', '',
            '7.5 x 18 ET45', '8 x 19 ET45', '',
            '5*114.3', '64.1', '12*1.5',
        ])."\n";

        $file = UploadedFile::fake()->createWithContent('vehicle.csv', $csv);

        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/catalog/import/vehicle', ['file' => $file])
            ->assertStatus(202);

        $this->assertDatabaseHas('vehicle_makes', ['name' => 'Acura']);
        $this->assertDatabaseHas('vehicle_models', ['name' => 'CDX', 'generation' => 'CDX']);
        $this->assertDatabaseHas('vehicle_modifications', ['name' => '1.5 T', 'year' => 2016]);
        $this->assertDatabaseHas('vehicle_tire_sizes', [
            'type' => 'stock',
            'position' => null,
            'width' => 235,
            'profile' => 50,
            'diameter' => '18',
        ]);
        $this->assertDatabaseHas('vehicle_tire_sizes', [
            'type' => 'optional',
            'position' => null,
            'width' => 235,
            'profile' => 45,
            'diameter' => '19',
        ]);
        $this->assertDatabaseHas('vehicle_wheel_specs', [
            'type' => 'stock',
            'position' => null,
            'width' => 7.5,
            'diameter' => 18,
            'et' => 45,
            'pcd' => '5*114.3',
            'hub_diameter' => 64.1,
            'bolts' => '12*1.5',
        ]);
        $this->assertDatabaseHas('vehicle_wheel_specs', [
            'type' => 'optional',
            'position' => null,
            'width' => 8,
            'diameter' => 19,
            'et' => 45,
        ]);
    }

    /** @group slow */
    public function test_import_is_idempotent(): void
    {
        Storage::fake('local');

        $csv = implode(';', [
            'Acura', 'CDX', 'CDX', '1.5 T', '2016',
            '235/50 R18', '', '', '', '', '',
            '5*114.3', '64.1', '12*1.5',
        ])."\n";

        $file1 = UploadedFile::fake()->createWithContent('vehicle1.csv', $csv);
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/catalog/import/vehicle', ['file' => $file1])
            ->assertStatus(202);

        $file2 = UploadedFile::fake()->createWithContent('vehicle2.csv', $csv);
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/catalog/import/vehicle', ['file' => $file2])
            ->assertStatus(202);

        $this->assertDatabaseCount('vehicle_makes', 1);
        $this->assertDatabaseCount('vehicle_models', 1);
        $this->assertDatabaseCount('vehicle_modifications', 1);
        $this->assertDatabaseCount('vehicle_tire_sizes', 1);
    }

    /** @group slow */
    public function test_import_parses_staggered_tire_sizes(): void
    {
        Storage::fake('local');

        $csv = implode(';', [
            'Acura', 'NSX', 'NSX', '3.0 V6', '2001',
            '', '', '215/40 R17,245/40 R17',
            '', '', '',
            '5*114.3', '70.1', '12*1.5',
        ])."\n";

        $file = UploadedFile::fake()->createWithContent('vehicle.csv', $csv);

        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/catalog/import/vehicle', ['file' => $file])
            ->assertStatus(202);

        $this->assertDatabaseHas('vehicle_tire_sizes', [
            'type' => 'optional',
            'position' => 'Front',
            'width' => 215,
            'profile' => 40,
            'diameter' => '17',
        ]);
        $this->assertDatabaseHas('vehicle_tire_sizes', [
            'type' => 'optional',
            'position' => 'Rear',
            'width' => 245,
            'profile' => 40,
            'diameter' => '17',
        ]);
        $this->assertDatabaseCount('vehicle_tire_sizes', 2);
    }

    /** @group slow */
    public function test_import_parses_staggered_wheel_specs(): void
    {
        Storage::fake('local');

        $csv = implode(';', [
            'Acura', 'NSX', 'NSX', '3.0 V6', '2001',
            '', '', '',
            '', '', '7.5 x 17 ET40,9 x 17 ET30',
            '5*114.3', '70.1', '12*1.5',
        ])."\n";

        $file = UploadedFile::fake()->createWithContent('vehicle.csv', $csv);

        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/catalog/import/vehicle', ['file' => $file])
            ->assertStatus(202);

        $this->assertDatabaseHas('vehicle_wheel_specs', [
            'type' => 'optional',
            'position' => 'Front',
            'width' => 7.5,
            'diameter' => 17,
            'et' => 40,
            'pcd' => '5*114.3',
            'hub_diameter' => 70.1,
            'bolts' => '12*1.5',
        ]);
        $this->assertDatabaseHas('vehicle_wheel_specs', [
            'type' => 'optional',
            'position' => 'Rear',
            'width' => 9,
            'diameter' => 17,
            'et' => 30,
        ]);
        $this->assertDatabaseCount('vehicle_wheel_specs', 2);
    }

    /** @group slow */
    public function test_import_parses_alternative_sizes(): void
    {
        Storage::fake('local');

        $csv = implode(';', [
            'Acura', 'Test', 'Test', '1.0', '2020',
            '215/50 R17|225/45 R18', '', '', '', '', '',
            '5*114.3', '64.1', '12*1.5',
        ])."\n";

        $file = UploadedFile::fake()->createWithContent('vehicle.csv', $csv);

        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/catalog/import/vehicle', ['file' => $file])
            ->assertStatus(202);

        $this->assertDatabaseCount('vehicle_tire_sizes', 2);
    }

    /** @group slow */
    public function test_import_continues_after_row_error(): void
    {
        Storage::fake('local');

        $validRow = implode(';', [
            'Acura', 'Good', 'Good', '1.0', '2020',
            '235/50 R18', '', '', '', '', '',
            '5*114.3', '64.1', '12*1.5',
        ]);

        $badRow = implode(';', [
            '', '', '', '', '',
            'NOT A SIZE', '', '', '', '', '',
            '', '', '',
        ]);

        $csv = $validRow."\n".$badRow."\n".$validRow."\n";

        $file = UploadedFile::fake()->createWithContent('vehicle.csv', $csv);

        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/catalog/import/vehicle', ['file' => $file])
            ->assertStatus(202);

        $import = ProductImport::latest('id')->first();
        $this->assertNotNull($import);
        $this->assertEquals('completed', $import->status->value);
        $this->assertEquals(1, $import->failed_rows);
        $this->assertNotEmpty($import->errors);
    }

    /** @group slow */
    public function test_import_skips_empty_tire_wheel_columns(): void
    {
        Storage::fake('local');

        $csv = implode(';', [
            'Acura', 'Test', 'Test', '1.0', '2020',
            '235/50 R18', '', '', '', '', '',
            '5*114.3', '64.1', '12*1.5',
        ])."\n";

        $file = UploadedFile::fake()->createWithContent('vehicle.csv', $csv);

        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/catalog/import/vehicle', ['file' => $file])
            ->assertStatus(202);

        $this->assertDatabaseCount('vehicle_tire_sizes', 1);
        $this->assertDatabaseCount('vehicle_wheel_specs', 0);
    }

    /** @group slow */
    public function test_import_transitions_to_completed(): void
    {
        Storage::fake('local');

        $csv = implode(';', [
            'Acura', 'Test', 'Test', '1.0', '2020',
            '235/50 R18', '', '', '', '', '',
            '5*114.3', '64.1', '12*1.5',
        ])."\n";

        $file = UploadedFile::fake()->createWithContent('vehicle.csv', $csv);

        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/catalog/import/vehicle', ['file' => $file])
            ->assertStatus(202);

        $this->assertDatabaseHas('product_imports', [
            'status' => 'completed',
            'type' => ImportType::Vehicle->value,
        ]);

        $import = ProductImport::where('type', ImportType::Vehicle->value)->first();
        $this->assertNotNull($import);
        $this->assertNotNull($import->finished_at);
    }

    /** @group slow */
    public function test_different_years_create_separate_modifications(): void
    {
        Storage::fake('local');

        $row2016 = implode(';', [
            'Acura', 'Test', 'Test', '1.0', '2016',
            '235/50 R18', '', '', '', '', '',
            '5*114.3', '64.1', '12*1.5',
        ]);
        $row2017 = implode(';', [
            'Acura', 'Test', 'Test', '1.0', '2017',
            '235/50 R18', '', '', '', '', '',
            '5*114.3', '64.1', '12*1.5',
        ]);

        $csv = $row2016."\n".$row2017."\n";

        $file = UploadedFile::fake()->createWithContent('vehicle.csv', $csv);

        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/catalog/import/vehicle', ['file' => $file])
            ->assertStatus(202);

        $this->assertDatabaseCount('vehicle_makes', 1);
        $this->assertDatabaseCount('vehicle_models', 1);
        $this->assertDatabaseCount('vehicle_modifications', 2);
        $this->assertDatabaseHas('vehicle_modifications', ['year' => 2016]);
        $this->assertDatabaseHas('vehicle_modifications', ['year' => 2017]);
    }
}
