<?php

namespace Tests\Unit\Models\Catalog\Builders;

use App\Models\Catalog\Brand\Brand;
use App\Models\Catalog\Warehouse\Stock;
use App\Models\Catalog\Warehouse\Warehouse;
use App\Models\Catalog\Wheel\WheelProduct;
use App\Models\Delivery\CatalogPrice;
use App\Models\Delivery\City;
use App\Models\Delivery\Region;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Тесты методов фильтрации WheelProductBuilder. */
class WheelProductBuilderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        WheelProduct::factory()->createMany([
            ['width' => 6.5, 'diameter' => 16, 'pcd' => '5x112', 'et' => 35, 'hub_diameter' => 57.1, 'type' => 'alloy', 'color' => 'black', 'is_bestseller' => true, 'is_new' => false],
            ['width' => 7.0, 'diameter' => 17, 'pcd' => '5x114.3', 'et' => 40, 'hub_diameter' => 66.6, 'type' => 'steel', 'color' => 'silver', 'is_bestseller' => false, 'is_new' => true],
            ['width' => 7.5, 'diameter' => 18, 'pcd' => '5x120', 'et' => 45, 'hub_diameter' => 72.6, 'type' => 'forged', 'color' => 'black', 'is_bestseller' => true, 'is_new' => true],
        ]);
    }

    public function test_by_widths_filters_by_multiple_widths(): void
    {
        $result = WheelProduct::query()->byWidths([6.5, 7.0])->get();

        $this->assertCount(2, $result);
        $this->assertEqualsCanonicalizing([6.5, 7.0], $result->pluck('width')->all());
    }

    public function test_by_diameters_filters_by_multiple_diameters(): void
    {
        $result = WheelProduct::query()->byDiameters([16, 17])->get();

        $this->assertCount(2, $result);
        $this->assertEqualsCanonicalizing([16, 17], $result->pluck('diameter')->all());
    }

    public function test_by_pcds_filters_by_multiple_pcds(): void
    {
        $result = WheelProduct::query()->byPcds(['5x112', '5x120'])->get();

        $this->assertCount(2, $result);
    }

    public function test_by_ets_filters_by_multiple_ets(): void
    {
        $result = WheelProduct::query()->byEts([35, 45])->get();

        $this->assertCount(2, $result);
        $this->assertEqualsCanonicalizing([35.0, 45.0], $result->pluck('et')->all());
    }

    public function test_by_hub_diameters_filters_by_multiple_values(): void
    {
        $result = WheelProduct::query()->byHubDiameters([57.1, 66.6])->get();

        $this->assertCount(2, $result);
    }

    public function test_bestseller_filters_correctly(): void
    {
        $result = WheelProduct::query()->bestseller(true)->get();

        $this->assertCount(2, $result);
        $this->assertTrue($result->every(fn ($w) => $w->is_bestseller));
    }

    public function test_is_new_filters_correctly(): void
    {
        $result = WheelProduct::query()->isNew(true)->get();

        $this->assertCount(2, $result);
        $this->assertTrue($result->every(fn ($w) => $w->is_new));
    }

    public function test_filters_can_be_combined(): void
    {
        $result = WheelProduct::query()
            ->byWidths([7.0, 7.5])
            ->byDiameters([17, 18])
            ->bestseller(false)
            ->get();

        $this->assertCount(1, $result);
        $this->assertEquals(7.0, $result->first()->width);
    }

    public function test_combined_filters_with_no_match_return_empty(): void
    {
        $result = WheelProduct::query()
            ->byWidths([6.5])
            ->byPcds(['5x120'])
            ->get();

        $this->assertCount(0, $result);
    }

    public function test_by_catalog_filters_applies_dimension_and_slug_filters(): void
    {
        $nokian = Brand::factory()->create(['name' => 'Nokian', 'slug' => 'nokian']);
        $kama = Brand::factory()->create(['name' => 'Kama', 'slug' => 'kama']);

        $alloyNokian = WheelProduct::factory()->create(['brand_id' => $nokian->id, 'type' => 'alloy', 'pcd' => '5*112']);
        WheelProduct::factory()->create(['brand_id' => $nokian->id, 'type' => 'steel', 'pcd' => '5*112']);
        WheelProduct::factory()->create(['brand_id' => $kama->id, 'type' => 'alloy', 'pcd' => '5*114.3']);

        $result = WheelProduct::query()
            ->byCatalogFilters(1, ['type' => 'alloy', 'pcd' => ['5*112'], 'brand' => 'nokian'])
            ->get();

        $this->assertSame([$alloyNokian->id], $result->pluck('id')->all());
    }

    public function test_by_catalog_filters_delivery_bucket(): void
    {
        $city = $this->createCity();
        $fast = WheelProduct::factory()->create();
        $this->createCatalogPrice($this->createStock($fast), $city, deliveryMin: 1);

        $slow = WheelProduct::factory()->create();
        $this->createCatalogPrice($this->createStock($slow), $city, deliveryMin: 6);

        $result = WheelProduct::query()
            ->byCatalogFilters($city->id, ['delivery' => ['after5days']])
            ->get();

        $this->assertSame([$slow->id], $result->pluck('id')->all());
    }

    public function test_by_catalog_filters_requires_city_price(): void
    {
        $city = $this->createCity();
        $priced = WheelProduct::factory()->create();
        $this->createCatalogPrice($this->createStock($priced), $city, price: 1000);

        $unpriced = WheelProduct::factory()->create();
        $this->createStock($unpriced);

        $result = WheelProduct::query()
            ->byCatalogFilters($city->id, [], requireCityPrice: true)
            ->get();

        $this->assertSame([$priced->id], $result->pluck('id')->all());
    }

    private function createCity(): City
    {
        return City::create(['region_id' => Region::create(['code' => '74', 'name' => 'Область'])->id, 'name' => 'Город', 'sort' => 1]);
    }

    private function createStock(WheelProduct $wheel, array $overrides = []): Stock
    {
        return Stock::create(array_merge([
            'stockable_type' => $wheel->getMorphClass(),
            'stockable_id' => $wheel->id,
            'warehouse_id' => Warehouse::factory()->create()->id,
            'quantity' => 5,
            'price' => 1000,
        ], $overrides));
    }

    private function createCatalogPrice(
        Stock $stock,
        City $city,
        ?float $price = 1000,
        ?int $deliveryMin = 2,
    ): CatalogPrice {
        return CatalogPrice::create([
            'stock_id' => $stock->id,
            'city_id' => $city->id,
            'price' => $price,
            'delivery_min' => $deliveryMin,
            'delivery_max' => $deliveryMin,
        ]);
    }
}
