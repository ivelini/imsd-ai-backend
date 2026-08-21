<?php

namespace Tests\Feature\Catalog;

use App\Models\Catalog\Brand\Brand;
use App\Models\Catalog\Warehouse\Stock;
use App\Models\Catalog\Warehouse\Warehouse;
use App\Models\Catalog\Wheel\WheelProduct;
use App\Models\Delivery\CatalogPrice;
use App\Models\Delivery\City;
use App\Models\Delivery\Region;
use App\Services\Cache\Catalog\WheelFilterValuesCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Фасетные значения фильтра дисков для города по умолчанию. */
class GetWheelFilterValuesTest extends TestCase
{
    use RefreshDatabase;

    private const PATH = '/api/reference/filter/wheel';

    private Region $region;

    private City $defaultCity;

    private City $otherCity;

    protected function setUp(): void
    {
        parent::setUp();

        $this->region = Region::create(['code' => '74', 'name' => 'Челябинская область']);
        $this->defaultCity = $this->createCity('Челябинск');
        $this->otherCity = $this->createCity('Екатеринбург');
    }

    public function test_returns_all_facet_keys(): void
    {
        $wheel = WheelProduct::factory()->create([
            'width' => 6.5,
            'diameter' => 16,
            'pcd' => '5*112',
            'et' => 38.0,
            'hub_diameter' => 66.1,
            'type' => 'alloy',
            'color' => 'серебристый',
        ]);
        $stock = $this->createStock($wheel);
        $this->createCatalogPrice($stock, $this->defaultCity, price: 2500, deliveryMin: 1);

        $response = $this->getJson(self::PATH);

        $response->assertOk();
        $data = $response->json('data');

        $this->assertEqualsCanonicalizing(
            ['width', 'diameter', 'pcd', 'et', 'hub_diameter', 'type', 'color', 'brand', 'country', 'delivery', 'price'],
            array_keys($data),
        );
        $this->assertEquals(['min' => 2500.0, 'max' => 2500.0], $data['price']);
    }

    public function test_excludes_unpublished_and_out_of_stock(): void
    {
        $published = WheelProduct::factory()->create(['width' => 6.5]);
        $this->createCatalogPrice($this->createStock($published), $this->defaultCity, price: 3000);

        $unpublished = WheelProduct::factory()->create(['width' => 9.5, 'is_published' => false]);
        $this->createCatalogPrice($this->createStock($unpublished), $this->defaultCity, price: 1);

        $outOfStock = WheelProduct::factory()->create(['width' => 8.5]);
        $this->createCatalogPrice($this->createStock($outOfStock, ['quantity' => 0]), $this->defaultCity, price: 1);

        $data = $this->getJson(self::PATH)->json('data');

        $this->assertSame([['label' => '6.5', 'value' => '6.5']], $data['width']);
        $this->assertEquals(['min' => 3000.0, 'max' => 3000.0], $data['price']);
    }

    public function test_price_range_uses_default_city_prices(): void
    {
        $wheelA = WheelProduct::factory()->create();
        $stockA = $this->createStock($wheelA);
        $this->createCatalogPrice($stockA, $this->defaultCity, price: 1000);

        $wheelB = WheelProduct::factory()->create();
        $stockB = $this->createStock($wheelB);
        $this->createCatalogPrice($stockB, $this->defaultCity, price: 5000);

        $this->createCatalogPrice($stockA, $this->otherCity, price: 1);

        $data = $this->getJson(self::PATH)->json('data');

        $this->assertEquals(['min' => 1000.0, 'max' => 5000.0], $data['price']);
    }

    public function test_delivery_bucket_uses_min_delivery_min(): void
    {
        $wheel = WheelProduct::factory()->create();
        $this->createCatalogPrice($this->createStock($wheel), $this->defaultCity, deliveryMin: 0);
        $this->createCatalogPrice($this->createStock($wheel), $this->defaultCity, deliveryMin: 3);

        $data = $this->getJson(self::PATH)->json('data');

        $this->assertSame([['label' => 'Сегодня', 'value' => 'today']], $data['delivery']);
    }

    public function test_pcd_and_et_facets_raw_values(): void
    {
        $wheel = WheelProduct::factory()->create(['pcd' => '5*112', 'et' => 38.0]);
        $this->createCatalogPrice($this->createStock($wheel), $this->defaultCity);

        $data = $this->getJson(self::PATH)->json('data');

        $this->assertSame([['label' => '5*112', 'value' => '5*112']], $data['pcd']);
        $this->assertSame([['label' => '38.0', 'value' => '38.0']], $data['et']);
    }

    public function test_type_values_map_enum_labels(): void
    {
        $alloy = WheelProduct::factory()->create(['type' => 'alloy']);
        $this->createCatalogPrice($this->createStock($alloy), $this->defaultCity);

        $steel = WheelProduct::factory()->create(['type' => 'steel']);
        $this->createCatalogPrice($this->createStock($steel), $this->defaultCity);

        $data = $this->getJson(self::PATH)->json('data');

        $this->assertSame([
            ['label' => 'Литые', 'value' => 'alloy'],
            ['label' => 'Стальные', 'value' => 'steel'],
        ], $data['type']);
    }

    public function test_type_filter_narrows_facets(): void
    {
        $alloy = WheelProduct::factory()->create(['type' => 'alloy', 'width' => 6.5]);
        $this->createCatalogPrice($this->createStock($alloy), $this->defaultCity, price: 1000);

        $steel = WheelProduct::factory()->create(['type' => 'steel', 'width' => 7.5]);
        $this->createCatalogPrice($this->createStock($steel), $this->defaultCity, price: 3000);

        $data = $this->getJson(self::PATH.'?type=alloy')->json('data');

        $this->assertSame([['label' => 'Литые', 'value' => 'alloy']], $data['type']);
        $this->assertSame([['label' => '6.5', 'value' => '6.5']], $data['width']);
        $this->assertEquals(['min' => 1000.0, 'max' => 1000.0], $data['price']);
    }

    public function test_brand_slug_filter_narrows_facets(): void
    {
        $nokian = Brand::factory()->create(['name' => 'Nokian', 'slug' => 'nokian']);
        $kama = Brand::factory()->create(['name' => 'Kama', 'slug' => 'kama']);

        $wheelA = WheelProduct::factory()->create(['brand_id' => $nokian->id, 'width' => 6.5]);
        $this->createCatalogPrice($this->createStock($wheelA), $this->defaultCity);

        $wheelB = WheelProduct::factory()->create(['brand_id' => $kama->id, 'width' => 7.5]);
        $this->createCatalogPrice($this->createStock($wheelB), $this->defaultCity);

        $data = $this->getJson(self::PATH.'?brand=nokian')->assertOk()->json('data');

        $this->assertSame([['label' => 'Nokian', 'value' => 'nokian']], $data['brand']);
        $this->assertSame([['label' => '6.5', 'value' => '6.5']], $data['width']);
    }

    public function test_invalid_filters_rejected(): void
    {
        $this->getJson(self::PATH.'?type=bogus')->assertStatus(422);
        $this->getJson(self::PATH.'?diameter[]=abc')->assertStatus(422);
        $this->getJson(self::PATH.'?delivery=bogus')->assertStatus(422);
        $this->getJson(self::PATH.'?city_id=999999')->assertStatus(422);
    }

    public function test_response_is_cached(): void
    {
        $wheel = WheelProduct::factory()->create();
        $this->createCatalogPrice($this->createStock($wheel), $this->defaultCity);

        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->getJson(self::PATH)->assertOk();
        DB::flushQueryLog();

        $this->getJson(self::PATH)->assertOk();

        $this->assertSame(0, count(DB::getQueryLog()));
    }

    public function test_cache_key_includes_filters(): void
    {
        $narrow = WheelProduct::factory()->create(['width' => 6.5]);
        $this->createCatalogPrice($this->createStock($narrow), $this->defaultCity);

        $other = WheelProduct::factory()->create(['width' => 7.5]);
        $this->createCatalogPrice($this->createStock($other), $this->defaultCity);

        $narrowData = $this->getJson(self::PATH.'?width[]=6.5')->json('data');
        $this->assertSame([['label' => '6.5', 'value' => '6.5']], $narrowData['width']);

        $fullData = $this->getJson(self::PATH)->json('data');
        $this->assertSame([
            ['label' => '6.5', 'value' => '6.5'],
            ['label' => '7.5', 'value' => '7.5'],
        ], $fullData['width']);
    }

    public function test_price_range_uses_requested_city(): void
    {
        $wheel = WheelProduct::factory()->create();
        $stock = $this->createStock($wheel);
        $this->createCatalogPrice($stock, $this->defaultCity, price: 5000);
        $this->createCatalogPrice($stock, $this->otherCity, price: 1000);

        $data = $this->getJson(self::PATH.'?city_id='.$this->otherCity->id)->json('data');

        $this->assertEquals(['min' => 1000.0, 'max' => 1000.0], $data['price']);
    }

    public function test_forget_invalidates_all_variants(): void
    {
        $wheel = WheelProduct::factory()->create(['width' => 6.5]);
        $stock = $this->createStock($wheel);
        $price = $this->createCatalogPrice($stock, $this->defaultCity, price: 1000);

        $this->getJson(self::PATH.'?width[]=6.5')->assertOk();

        $price->update(['price' => 9999]);

        app(WheelFilterValuesCacheService::class)->forget();

        $data = $this->getJson(self::PATH.'?width[]=6.5')->json('data');

        $this->assertEquals(['min' => 9999.0, 'max' => 9999.0], $data['price']);
    }

    private function createCity(string $name): City
    {
        return City::create(['region_id' => $this->region->id, 'name' => $name, 'sort' => 1]);
    }

    /** @param  array<string, mixed>  $overrides */
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
