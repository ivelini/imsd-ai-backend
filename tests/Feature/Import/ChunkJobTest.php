<?php

namespace Tests\Feature\Import;

use App\Actions\Catalog\PopulateCatalogPrices;
use App\DTOs\Catalog\PopulateCatalogPricesInput;
use App\DTOs\Import\ImportMasterJobInput;
use App\Enums\Import\ImportType;
use App\Jobs\CatalogImport\ChunkJob;
use App\Jobs\CatalogImport\ImportMasterJob;
use App\Models\Catalog\Tire\TireProduct;
use App\Models\Catalog\Warehouse\Stock;
use App\Models\Catalog\Warehouse\Warehouse;
use App\Models\Delivery\CatalogPrice;
use App\Models\Delivery\City;
use App\Models\Delivery\Region;
use App\Models\System\ProductImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Накопление затронутых остатков в product_imports и пересчёт после импорта. */
class ChunkJobTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        @unlink(storage_path('app/test-chunks/chunk_test.json'));
        @rmdir(storage_path('app/test-chunks'));
        @rmdir(storage_path('app/import/wheels'));

        parent::tearDown();
    }

    public function test_chunk_job_accumulates_affected_stock_ids(): void
    {
        $import = ProductImport::create([
            'original_filename' => 'test.xlsx', 'type' => ImportType::Tire, 'status' => 'processing',
        ]);

        $rows = [
            $this->tireRow('T-001', 'WH-1'),
            $this->tireRow('T-001', 'WH-1'),
            $this->tireRow('T-002', null),
        ];
        $chunkPath = $this->writeChunk($rows);

        ChunkJob::dispatchSync($import->id, $chunkPath, ImportType::Tire);

        $stockId = Stock::firstOrFail()->id;
        $this->assertSame([$stockId], $import->refresh()->affected_stock_ids);
    }

    public function test_chunk_job_keeps_stock_ids_null_without_warehouse_rows(): void
    {
        $import = ProductImport::create([
            'original_filename' => 'test.xlsx', 'type' => ImportType::Tire, 'status' => 'processing',
        ]);
        $chunkPath = $this->writeChunk([$this->tireRow('T-001', null)]);

        ChunkJob::dispatchSync($import->id, $chunkPath, ImportType::Tire);

        $this->assertNull($import->refresh()->affected_stock_ids);
        $this->assertSame(0, Stock::count());
    }

    public function test_import_finally_recalculates_only_affected_stocks(): void
    {
        $path = base_path('documentations/import/wheels.xlsx');
        if (! file_exists($path)) {
            $this->markTestSkipped('Файл wheels.xlsx не найден.');
        }

        $city = $this->createCity();
        $foreignTire = TireProduct::factory()->create();
        $foreignStock = Stock::create([
            'stockable_type' => $foreignTire->getMorphClass(),
            'stockable_id' => $foreignTire->id,
            'warehouse_id' => Warehouse::factory()->create()->id,
            'quantity' => 1,
            'purchase_price' => 500,
        ]);

        app(PopulateCatalogPrices::class)->execute(new PopulateCatalogPricesInput);

        $foreignPriceBefore = CatalogPrice::where('stock_id', $foreignStock->id)->where('city_id', $city->id)->value('price');
        $this->assertSame(500.0, $foreignPriceBefore);

        $import = ProductImport::create([
            'original_filename' => 'wheels.xlsx', 'type' => ImportType::Wheel, 'status' => 'pending',
        ]);

        ImportMasterJob::dispatchSync(new ImportMasterJobInput(
            importId: $import->id,
            filePath: $path,
            chunkSize: 500,
            chunkPath: 'import/wheels',
            importType: ImportType::Wheel,
            requiredColumns: config('wheel_import.required_columns', []),
            columnMap: config('wheel_import.column_map', []),
        ));

        $affected = $import->refresh()->affected_stock_ids;
        $this->assertNotEmpty($affected);

        foreach ($affected as $stockId) {
            $this->assertDatabaseHas('catalog_prices', ['stock_id' => $stockId, 'city_id' => $city->id]);
        }
        $this->assertSame(
            500.0,
            CatalogPrice::where('stock_id', $foreignStock->id)->where('city_id', $city->id)->value('price'),
        );
    }

    /** @param  array<string, mixed>  $row */
    private function tireRow(string $ean, ?string $warehouseName): array
    {
        return [
            'ean' => $ean,
            'brand_name' => 'Brand A',
            'season_raw' => 'зимняя',
            'name' => 'Tire '.$ean,
            'width' => '205',
            'profile' => '55',
            'diameter' => '16',
            'load_speed_index' => '91T',
            'warehouse_name' => $warehouseName,
            'quantity' => $warehouseName !== null ? '5' : null,
            'purchase_price' => $warehouseName !== null ? '100' : null,
        ];
    }

    /** @param  array<int, array<string, mixed>>  $rows */
    private function writeChunk(array $rows): string
    {
        $dir = storage_path('app/test-chunks');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $path = $dir.'/chunk_test.json';
        file_put_contents($path, json_encode(['batch_id' => 'test', 'rows' => $rows], JSON_UNESCAPED_UNICODE));

        return $path;
    }

    private function createCity(): City
    {
        $region = Region::create(['code' => '74', 'name' => 'Челябинская область']);

        return City::create(['region_id' => $region->id, 'name' => 'Челябинск', 'sort' => 1]);
    }
}
