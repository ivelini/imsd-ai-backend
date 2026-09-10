<?php

namespace Tests\Feature\Admin\Catalog;

use App\Actions\Import\Tire\UpsertTireProduct;
use App\DTOs\TireImport\ImportTireRow;
use App\Models\Catalog\Brand\Brand;
use App\Models\Catalog\Model\ProductModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Генерация slug у товаров при импорте (генерация через админку — TireResourceTest/WheelResourceTest). */
class ProductSlugTest extends TestCase
{
    use RefreshDatabase;

    private Brand $brand;

    private ProductModel $tireModel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->brand = Brand::factory()->create(['name' => 'Nokian', 'slug' => 'nokian']);
        $this->tireModel = ProductModel::create([
            'brand_id' => $this->brand->id, 'name' => 'Hakka', 'slug' => 'hakka', 'type' => 'tire',
        ]);
    }

    public function test_tire_import_generates_slug_and_name(): void
    {
        app(UpsertTireProduct::class)->execute($this->tireRow(ean: 'TIRE-1', width: 215));

        $this->assertDatabaseHas('tire_products', [
            'ean' => 'TIRE-1',
            'name' => 'Шина летняя Nokian Hakka 215/60 R16',
            'slug' => 'nokian-hakka-215-60-r16',
        ]);
    }

    public function test_tire_import_recalculates_on_reimport(): void
    {
        $upsert = app(UpsertTireProduct::class);
        $upsert->execute($this->tireRow(ean: 'TIRE-2', width: 215));
        $upsert->execute($this->tireRow(ean: 'TIRE-2', width: 225));

        $this->assertDatabaseHas('tire_products', [
            'ean' => 'TIRE-2',
            'name' => 'Шина летняя Nokian Hakka 225/60 R16',
            'slug' => 'nokian-hakka-225-60-r16',
        ]);
    }

    public function test_tire_collision_gets_suffix(): void
    {
        $upsert = app(UpsertTireProduct::class);
        $upsert->execute($this->tireRow(ean: 'TIRE-3', width: 215));
        $upsert->execute($this->tireRow(ean: 'TIRE-4', width: 215));

        $this->assertDatabaseHas('tire_products', ['ean' => 'TIRE-3', 'slug' => 'nokian-hakka-215-60-r16']);
        $this->assertDatabaseHas('tire_products', ['ean' => 'TIRE-4', 'slug' => 'nokian-hakka-215-60-r16-2']);
    }

    private function tireRow(string $ean, ?int $width): ImportTireRow
    {
        return new ImportTireRow(
            ean: $ean,
            brand_name: 'Nokian',
            season_raw: 'летняя',
            country_name: null,
            name: 'Hakka',
            width: $width,
            profile: 60,
            diameter: '16',
            load_speed_index: null,
            is_runflat_raw: null,
            is_studded_raw: null,
            warehouse_name: null,
            quantity: null,
            purchase_price: null,
            minimum_market_price: null,
            euroLabel: null,
            description: null,
            description_present: false,
            origin_vendor: null,
            origin_manufacture_country: null,
            origin_manufacture_year: null,
            origin_present: false,
            promos: [],
        );
    }
}
