<?php

namespace Tests\Feature\Admin\Catalog;

use App\Actions\TireImport\ParseImportFile;
use App\Actions\TireImport\UpsertStock;
use App\Actions\TireImport\UpsertWheelProduct;
use App\DTOs\TireImport\ParseImportFileInput;
use App\DTOs\TireImport\UpsertStockInput;
use App\DTOs\TireImport\UpsertWheelProductInput;
use App\Enums\Catalog\WheelType;
use App\Enums\Import\ImportType;
use App\Models\Auth\Admin;
use App\Models\Auth\AdminRole;
use App\Models\Catalog\WheelProduct;
use App\Services\TireImport\ReferenceResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Импорт дисков. */
class WheelImportTest extends TestCase
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

    public function test_upload_requires_auth(): void
    {
        $this->postJson('/api/admin/catalog/wheels/import')->assertUnauthorized();
    }

    public function test_upload_returns_202(): void
    {
        Bus::fake();
        Storage::fake('local');

        $file = UploadedFile::fake()->create('wheels.xlsx', 100);

        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/catalog/wheels/import', ['file' => $file])
            ->assertStatus(202)
            ->assertJsonStructure(['data' => ['import_id']]);

        $this->assertDatabaseHas('product_imports', ['type' => ImportType::Wheel->value, 'status' => 'pending']);
    }

    public function test_parse_wheels_xlsx(): void
    {
        $path = base_path('documentations/import/wheels.xlsx');
        if (! file_exists($path)) {
            $this->markTestSkipped('Файл wheels.xlsx не найден.');
        }

        $action = app(ParseImportFile::class);
        $chunkDir = storage_path('app/test-wheel-import');
        $result = $action->execute(new ParseImportFileInput(
            filePath: $path,
            batchId: 'wheel-test',
            chunkSize: 500,
            chunkDir: $chunkDir,
            requiredColumns: config('wheel_import.required_columns', []),
            columnMap: config('wheel_import.column_map', []),
        ));

        $this->assertGreaterThan(0, $result->totalRows);
        $this->assertNotEmpty($result->chunkFilePaths);

        foreach ($result->chunkFilePaths as $chunkPath) {
            @unlink($chunkPath);
        }
        @rmdir($chunkDir);
    }

    public function test_upsert_wheel_creates_product(): void
    {
        $resolver = app(ReferenceResolver::class);
        $upsert = new UpsertWheelProduct($resolver);

        $upsert->execute(new UpsertWheelProductInput(
            ean: 'TEST-WHEEL-001',
            brandName: 'Test Wheel Brand',
            name: 'Test Wheel Model',
            countryName: 'Китай',
            color: 'Черный',
            diameter: 16,
            width: '7',
            pcd1: '4',
            pcd2: '98',
            hubDiameter: '58.6',
            et: '28',
            wheelTypeRaw: 'Литые',
            supplierName: 'Test Sup',
            description: 'Test description',
        ));

        $this->assertDatabaseHas('wheel_products', ['ean' => 'TEST-WHEEL-001']);
        $this->assertDatabaseHas('brands', ['name' => 'Test Wheel Brand']);

        $wheel = WheelProduct::where('ean', 'TEST-WHEEL-001')->firstOrFail();
        $this->assertSame(WheelType::Alloy, $wheel->type);
        $this->assertSame('4*98', $wheel->pcd);
    }

    public function test_upsert_wheel_with_stock(): void
    {
        $resolver = app(ReferenceResolver::class);
        $upsertWheel = new UpsertWheelProduct($resolver);
        $upsertStock = app(UpsertStock::class);

        $upsertWheel->execute(new UpsertWheelProductInput(
            ean: 'TEST-WHEEL-002',
            brandName: 'Brand X',
            name: 'Model X',
            countryName: null,
            color: null,
            diameter: 15,
            width: '6',
            pcd1: '5',
            pcd2: '114.3',
            hubDiameter: null,
            et: null,
            wheelTypeRaw: null,
            supplierName: null,
            description: null,
        ));

        $wheel = WheelProduct::where('ean', 'TEST-WHEEL-002')->firstOrFail();
        $upsertStock->execute(new UpsertStockInput(
            stockableType: $wheel->getMorphClass(),
            stockableId: $wheel->id,
            warehouseName: 'Test WH',
            quantity: 10,
            purchasePrice: 5000,
        ));

        $this->assertDatabaseHas('stocks', [
            'stockable_type' => $wheel->getMorphClass(),
            'stockable_id' => $wheel->id,
            'quantity' => 10,
        ]);
    }
}
