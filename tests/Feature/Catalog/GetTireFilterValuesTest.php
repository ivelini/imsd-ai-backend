<?php

namespace Tests\Feature\Catalog;

use App\Models\Catalog\Country\Country;
use App\Models\Catalog\Tire\TireProduct;
use App\Models\Catalog\Warehouse\Stock;
use App\Models\Catalog\Warehouse\Warehouse;
use App\Models\Delivery\CatalogPrice;
use App\Models\Delivery\City;
use App\Models\Delivery\Region;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Фасетные значения фильтра шин для города по умолчанию. */
class GetTireFilterValuesTest extends TestCase
{
    use RefreshDatabase;

    private const PATH = '/api/reference/filter/tire';

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
        $tire = TireProduct::factory()->create([
            'width' => 215,
            'profile' => 60,
            'diameter' => '16',
            'season' => 'summer',
            'is_studded' => true,
            'country_id' => Country::create(['name' => 'Финляндия']),
        ]);
        $stock = $this->createStock($tire);
        $this->createCatalogPrice($stock, $this->defaultCity, price: 2500, deliveryMin: 1);

        $response = $this->getJson(self::PATH);

        $response->assertOk();
        $data = $response->json('data');

        $this->assertEqualsCanonicalizing(
            ['width', 'profile', 'diameter', 'season', 'studded', 'brand', 'country', 'delivery', 'price'],
            array_keys($data),
        );
        $this->assertEquals(['min' => 2500.0, 'max' => 2500.0], $data['price']);
    }

    public function test_excludes_unpublished_and_out_of_stock(): void
    {
        $published = TireProduct::factory()->create(['width' => 205]);
        $this->createCatalogPrice($this->createStock($published), $this->defaultCity, price: 3000);

        $unpublished = TireProduct::factory()->create(['width' => 999, 'is_published' => false]);
        $this->createCatalogPrice($this->createStock($unpublished), $this->defaultCity, price: 1);

        $outOfStock = TireProduct::factory()->create(['width' => 888]);
        $this->createCatalogPrice($this->createStock($outOfStock, ['quantity' => 0]), $this->defaultCity, price: 1);

        $data = $this->getJson(self::PATH)->json('data');

        $this->assertSame([['label' => 205, 'value' => 205]], $data['width']);
        $this->assertEquals(['min' => 3000.0, 'max' => 3000.0], $data['price']);
    }

    public function test_price_range_uses_default_city_prices(): void
    {
        $tireA = TireProduct::factory()->create();
        $stockA = $this->createStock($tireA);
        $this->createCatalogPrice($stockA, $this->defaultCity, price: 1000);

        $tireB = TireProduct::factory()->create();
        $stockB = $this->createStock($tireB);
        $this->createCatalogPrice($stockB, $this->defaultCity, price: 5000);

        $this->createCatalogPrice($stockA, $this->otherCity, price: 1);

        $data = $this->getJson(self::PATH)->json('data');

        $this->assertEquals(['min' => 1000.0, 'max' => 5000.0], $data['price']);
    }

    public function test_delivery_bucket_uses_min_delivery_min(): void
    {
        $tire = TireProduct::factory()->create();
        $this->createCatalogPrice($this->createStock($tire), $this->defaultCity, deliveryMin: 0);
        $this->createCatalogPrice($this->createStock($tire), $this->defaultCity, deliveryMin: 3);

        $data = $this->getJson(self::PATH)->json('data');

        $this->assertSame([['label' => 'Сегодня', 'value' => 'today']], $data['delivery']);
    }

    public function test_delivery_bucket_maps_after_five(): void
    {
        $tire = TireProduct::factory()->create();
        $this->createCatalogPrice($this->createStock($tire), $this->defaultCity, deliveryMin: 6);

        $data = $this->getJson(self::PATH)->json('data');

        $this->assertSame([['label' => 'После 5 дней', 'value' => 'after5days']], $data['delivery']);
    }

    public function test_response_is_cached(): void
    {
        $tire = TireProduct::factory()->create();
        $this->createCatalogPrice($this->createStock($tire), $this->defaultCity);

        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->getJson(self::PATH)->assertOk();
        DB::flushQueryLog();

        $this->getJson(self::PATH)->assertOk();

        $this->assertSame(0, count(DB::getQueryLog()));
    }

    public function test_season_values_map_enum_labels(): void
    {
        $winter = TireProduct::factory()->create(['season' => 'winter']);
        $this->createCatalogPrice($this->createStock($winter), $this->defaultCity);

        $summer = TireProduct::factory()->create(['season' => 'summer']);
        $this->createCatalogPrice($this->createStock($summer), $this->defaultCity);

        $data = $this->getJson(self::PATH)->json('data');

        $this->assertSame([
            ['label' => 'Зимняя', 'value' => 'winter'],
            ['label' => 'Летняя', 'value' => 'summer'],
        ], $data['season']);
    }

    public function test_price_range_zero_when_no_prices(): void
    {
        $tire = TireProduct::factory()->create();
        $this->createStock($tire);

        $data = $this->getJson(self::PATH)->json('data');

        $this->assertEquals(['min' => 0, 'max' => 0], $data['price']);
    }

    public function test_delivery_facet_excludes_null_delivery_min(): void
    {
        $tireA = TireProduct::factory()->create();
        $this->createCatalogPrice($this->createStock($tireA), $this->defaultCity, deliveryMin: 2);

        $tireB = TireProduct::factory()->create();
        $this->createCatalogPrice($this->createStock($tireB), $this->defaultCity, deliveryMin: null);

        $data = $this->getJson(self::PATH)->json('data');

        $this->assertSame([['label' => 'От 1 до 3 дней', 'value' => 'between1and3days']], $data['delivery']);
    }

    private function createCity(string $name): City
    {
        return City::create(['region_id' => $this->region->id, 'name' => $name, 'sort' => 1]);
    }

    /** @param  array<string, mixed>  $overrides */
    private function createStock(TireProduct $tire, array $overrides = []): Stock
    {
        return Stock::create(array_merge([
            'stockable_type' => $tire->getMorphClass(),
            'stockable_id' => $tire->id,
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
