<?php

namespace Tests\Feature\Catalog;

use App\DTOs\Catalog\OriginInfo;
use App\Models\Catalog\Brand\Brand;
use App\Models\Catalog\Model\ProductModel;
use App\Models\Catalog\Origin\ProductOrigin;
use App\Models\Catalog\Warehouse\Stock;
use App\Models\Catalog\Warehouse\Warehouse;
use App\Models\Catalog\Wheel\WheelProduct;
use App\Models\Delivery\CatalogPrice;
use App\Models\Delivery\City;
use App\Models\Delivery\DeliverySchedule;
use App\Models\Delivery\Region;
use App\Services\Cache\Catalog\WheelListCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Пагинированный список дисков каталога для города. */
class GetWheelListTest extends TestCase
{
    use RefreshDatabase;

    private const PATH = '/api/catalog/wheels';

    private Region $region;

    private City $defaultCity;

    private City $otherCity;

    protected function setUp(): void
    {
        parent::setUp();

        // Фиксация времени: до cutoff расписаний (детерминизм order_day_of_week)
        Carbon::setTestNow(now()->startOfDay()->addHours(10));

        $this->region = Region::create(['code' => '74', 'name' => 'Челябинская область']);
        $this->defaultCity = $this->createCity('Челябинск');
        $this->otherCity = $this->createCity('Екатеринбург');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_returns_paginated_shape(): void
    {
        $wheel = WheelProduct::factory()->create();
        $this->createCatalogPrice($this->createStock($wheel), $this->defaultCity);

        $response = $this->getJson(self::PATH);

        $response->assertOk();
        $this->assertEquals(
            [
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => 48,
                'total' => 1,
                'seo' => ['title' => str_replace('{city}', 'в Челябинске', config('shop.seo.title')), 'description' => config('shop.seo.description')],
            ],
            $response->json('meta'),
        );
        $this->assertEqualsCanonicalizing(
            ['id', 'name', 'slug', 'brand', 'model', 'origin', 'width', 'diameter', 'pcd', 'et', 'hub_diameter', 'type', 'color', 'price', 'images'],
            array_keys($response->json('data.0')),
        );
        $this->assertSame($wheel->id, $response->json('data.0.id'));
    }

    public function test_origin_included_in_item(): void
    {
        $origin = ProductOrigin::create([
            'vendor' => new OriginInfo('Shandong Haohua Tire', '<p>Описание.</p>'),
            'manufacture_country' => new OriginInfo('100% Китай', null),
            'manufacture_year' => new OriginInfo('2024-2025', null),
        ]);

        $wheel = WheelProduct::factory()->create(['origin_id' => $origin->id]);
        $this->createCatalogPrice($this->createStock($wheel), $this->defaultCity);

        $this->getJson(self::PATH)
            ->assertOk()
            ->assertJsonPath('data.0.origin.vendor.badge', 'Shandong Haohua Tire')
            ->assertJsonPath('data.0.origin.vendor.description', '<p>Описание.</p>')
            ->assertJsonPath('data.0.origin.manufacture_country.badge', '100% Китай')
            ->assertJsonPath('data.0.origin.manufacture_year.badge', '2024-2025');
    }

    public function test_origin_null_when_absent(): void
    {
        $wheel = WheelProduct::factory()->create();
        $this->createCatalogPrice($this->createStock($wheel), $this->defaultCity);

        $this->getJson(self::PATH)
            ->assertOk()
            ->assertJsonPath('data.0.origin', null);
    }

    public function test_meta_seo_without_brand(): void
    {
        $wheel = WheelProduct::factory()->create();
        $this->createCatalogPrice($this->createStock($wheel), $this->defaultCity);

        $this->assertSame(
            ['title' => str_replace('{city}', 'в Челябинске', config('shop.seo.title')), 'description' => config('shop.seo.description')],
            $this->getJson(self::PATH)->json('meta.seo'),
        );
    }

    public function test_meta_seo_with_brand(): void
    {
        $brand = Brand::factory()->create(['name' => 'K&K', 'slug' => 'k-k', 'type' => 'wheel', 'description' => 'Диски']);
        $wheel = WheelProduct::factory()->create(['brand_id' => $brand->id]);
        $this->createCatalogPrice($this->createStock($wheel), $this->defaultCity);

        $seo = $this->getJson(self::PATH.'?brand=k-k')->json('meta.seo');

        $this->assertSame(
            ['title' => 'Диски K&K в Челябинске', 'description' => 'Диски'],
            $seo,
        );
    }

    public function test_returns_type_reference(): void
    {
        $wheel = WheelProduct::factory()->create(['type' => 'alloy']);
        $this->createCatalogPrice($this->createStock($wheel), $this->defaultCity);

        $type = $this->getJson(self::PATH)->json('data.0.type');

        $this->assertSame(['label' => 'Литые', 'value' => 'alloy'], $type);
    }

    public function test_returns_model_reference(): void
    {
        $brand = Brand::factory()->create();
        $model = ProductModel::factory()->create(['brand_id' => $brand->id]);
        $wheel = WheelProduct::factory()->create(['brand_id' => $brand->id, 'model_id' => $model->id]);
        $this->createCatalogPrice($this->createStock($wheel), $this->defaultCity);

        $modelData = $this->getJson(self::PATH)->json('data.0.model');

        $this->assertSame(
            ['id' => $model->id, 'name' => $model->name, 'slug' => $model->slug],
            $modelData,
        );
    }

    public function test_excludes_unpublished_out_of_stock_without_price(): void
    {
        $published = WheelProduct::factory()->create();
        $this->createCatalogPrice($this->createStock($published), $this->defaultCity);

        $unpublished = WheelProduct::factory()->create(['is_published' => false]);
        $this->createCatalogPrice($this->createStock($unpublished), $this->defaultCity);

        $outOfStock = WheelProduct::factory()->create();
        $this->createCatalogPrice($this->createStock($outOfStock, ['quantity' => 0]), $this->defaultCity);

        $unpriced = WheelProduct::factory()->create();
        $this->createStock($unpriced);

        $response = $this->getJson(self::PATH);

        $this->assertSame(1, $response->json('meta.total'));
        $this->assertSame($published->id, $response->json('data.0.id'));
    }

    public function test_price_is_min_across_stocks(): void
    {
        $wheel = WheelProduct::factory()->create();
        $this->createCatalogPrice($this->createStock($wheel), $this->defaultCity, price: 5000, deliveryMin: 2);
        $this->createCatalogPrice($this->createStock($wheel), $this->defaultCity, price: 3000, deliveryMin: 5);

        $item = $this->getJson(self::PATH)->json('data.0');

        // Цена — min по стокам; delivery — от склада с минимальной ценой, но без расписания → блок отсутствует
        $this->assertEquals(3000.0, $item['price']);
        $this->assertArrayNotHasKey('delivery', $item);
    }

    public function test_uses_requested_city_price(): void
    {
        $wheel = WheelProduct::factory()->create();
        $stock = $this->createStock($wheel);
        $this->createCatalogPrice($stock, $this->defaultCity, price: 2500);
        $this->createCatalogPrice($stock, $this->otherCity, price: 1);

        $data = $this->getJson(self::PATH.'?city_id='.$this->otherCity->id)->json('data');

        $this->assertEquals(1.0, $data[0]['price']);
    }

    public function test_catalog_filters(): void
    {
        $alloy = WheelProduct::factory()->create(['type' => 'alloy', 'width' => 6.5, 'pcd' => '5*112']);
        $this->createCatalogPrice($this->createStock($alloy), $this->defaultCity);

        $steel = WheelProduct::factory()->create(['type' => 'steel', 'width' => 7.5, 'pcd' => '5*114.3']);
        $this->createCatalogPrice($this->createStock($steel), $this->defaultCity);

        $data = $this->getJson(self::PATH.'?type=alloy&width[]=6.5&pcd[]=5*112')->json('data');

        $this->assertCount(1, $data);
        $this->assertSame($alloy->id, $data[0]['id']);
    }

    public function test_delivery_and_price_filters(): void
    {
        $fast = WheelProduct::factory()->create(['width' => 6.5]);
        $this->createCatalogPrice($this->createStock($fast), $this->defaultCity, price: 1000, deliveryMin: 1);

        $slow = WheelProduct::factory()->create(['width' => 7.5]);
        $this->createCatalogPrice($this->createStock($slow), $this->defaultCity, price: 3500, deliveryMin: 6);

        $data = $this->getJson(self::PATH.'?delivery[]=after5days&price_min=3000&price_max=4000')->json('data');

        $this->assertCount(1, $data);
        $this->assertSame($slow->id, $data[0]['id']);
    }

    public function test_city_slug_resolution(): void
    {
        $sluggedCity = City::create(['region_id' => $this->region->id, 'name' => 'Магнитогорск', 'sort' => 1, 'slug' => 'magnitogorsk']);

        $wheel = WheelProduct::factory()->create();
        $stock = $this->createStock($wheel);
        $this->createCatalogPrice($stock, $this->defaultCity, price: 5000);
        $this->createCatalogPrice($stock, $sluggedCity, price: 1000);

        // city_id имеет приоритет; несуществующий слаг — фолбэк на дефолтный город, не 422
        $this->assertEquals(1000.0, $this->getJson(self::PATH.'?city=magnitogorsk')->json('data.0.price'));
        $this->assertEquals(5000.0, $this->getJson(self::PATH.'?city=neizvestnyj')->json('data.0.price'));
    }

    public function test_sort_by_price_asc_and_default_order(): void
    {
        $expensive = WheelProduct::factory()->create();
        $this->createCatalogPrice($this->createStock($expensive), $this->defaultCity, price: 3000);

        $cheap = WheelProduct::factory()->create();
        $this->createCatalogPrice($this->createStock($cheap), $this->defaultCity, price: 1000);

        $data = $this->getJson(self::PATH.'?sort_by=price&sort_dir=asc')->json('data');

        $this->assertSame($cheap->id, $data[0]['id']);
        $this->assertSame($expensive->id, $data[1]['id']);

        $default = $this->getJson(self::PATH)->json('data');

        $this->assertSame($cheap->id, $default[0]['id']);
    }

    public function test_response_is_cached(): void
    {
        $wheel = WheelProduct::factory()->create();
        $this->createCatalogPrice($this->createStock($wheel), $this->defaultCity);

        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->getJson(self::PATH)->assertOk();
        DB::flushQueryLog();

        // Кеш-hit: тяжёлый листинг не пересчитывается — delivery считается вне кеша лёгкими запросами
        $response = $this->getJson(self::PATH);

        $this->assertStringNotContainsString('from "wheel_products"', collect(DB::getQueryLog())->implode('query'));
        $response->assertOk()->assertJsonPath('data.0.id', $wheel->id);
    }

    public function test_forget_invalidates_all_variants(): void
    {
        $wheel = WheelProduct::factory()->create();
        $price = $this->createCatalogPrice($this->createStock($wheel), $this->defaultCity, price: 1000);

        $this->getJson(self::PATH)->assertOk();

        $price->update(['price' => 9999]);

        app(WheelListCacheService::class)->forget();

        $this->assertEquals(9999.0, $this->getJson(self::PATH)->json('data.0.price'));
    }

    public function test_invalid_sort_and_pagination_rejected(): void
    {
        $this->getJson(self::PATH.'?sort_by=name')->assertStatus(422);
        $this->getJson(self::PATH.'?per_page=5')->assertStatus(422);
        $this->getJson(self::PATH.'?per_page=101')->assertStatus(422);
        $this->getJson(self::PATH.'?delivery=between1and3days')->assertStatus(422);
    }

    public function test_delivery_block_from_cheapest_warehouse_with_min_quantity(): void
    {
        // Дешёвый склад с quantity < 4 не участвует: delivery от склада с ценой 100 (qty 10)
        $wheel = WheelProduct::factory()->create();
        $cheapLowQty = $this->createStock($wheel, ['quantity' => 2, 'price' => 90]);
        $this->createCatalogPrice($cheapLowQty, $this->defaultCity, price: 90, deliveryMin: 1);
        $selected = $this->createStock($wheel, ['quantity' => 10, 'price' => 100]);
        $this->createCatalogPrice($selected, $this->defaultCity, price: 100, deliveryMin: 6, deliveryMax: 8);
        $this->createScheduleForToday($selected->warehouse, daysBefore: 3, daysAfter: 5);

        $item = $this->getJson(self::PATH)->json('data.0');

        // Цена — min по стокам; delivery — только от выбранного склада, плоских полей нет
        $this->assertEquals(90.0, $item['price']);
        $this->assertSame(
            ['delivery_min' => 6, 'delivery_max' => 8, 'order_day_of_week' => now()->dayOfWeekIso - 1],
            $item['delivery'],
        );
        $this->assertArrayNotHasKey('delivery_min', $item);
        $this->assertArrayNotHasKey('delivery_max', $item);
    }

    public function test_delivery_skips_warehouse_below_min_quantity(): void
    {
        $wheel = WheelProduct::factory()->create();
        $below = $this->createStock($wheel, ['quantity' => 3, 'price' => 100]);
        $this->createCatalogPrice($below, $this->defaultCity, price: 100, deliveryMin: 1, deliveryMax: 2);
        $this->createScheduleForToday($below->warehouse);

        $selected = $this->createStock($wheel, ['quantity' => 6, 'price' => 110]);
        $this->createCatalogPrice($selected, $this->defaultCity, price: 110, deliveryMin: 5, deliveryMax: 7);
        $this->createScheduleForToday($selected->warehouse);

        $delivery = $this->getJson(self::PATH)->json('data.0.delivery');

        $this->assertSame(5, $delivery['delivery_min']);
        $this->assertSame(7, $delivery['delivery_max']);
    }

    public function test_delivery_absent_without_warehouse_min_quantity(): void
    {
        $wheel = WheelProduct::factory()->create();
        $this->createCatalogPrice($this->createStock($wheel, ['quantity' => 3]), $this->defaultCity);

        $item = $this->getJson(self::PATH)->json('data.0');

        $this->assertArrayNotHasKey('delivery', $item);
    }

    public function test_delivery_absent_when_warehouse_has_no_schedule(): void
    {
        $wheel = WheelProduct::factory()->create();
        $this->createCatalogPrice($this->createStock($wheel, ['quantity' => 5]), $this->defaultCity);

        $item = $this->getJson(self::PATH)->json('data.0');

        $this->assertArrayNotHasKey('delivery', $item);
    }

    public function test_delivery_recalculated_on_cache_hit(): void
    {
        $wheel = WheelProduct::factory()->create();
        $stock = $this->createStock($wheel);
        $this->createCatalogPrice($stock, $this->defaultCity, deliveryMin: 6, deliveryMax: 8);
        $schedule = $this->createScheduleForToday($stock->warehouse);

        $first = $this->getJson(self::PATH)->json('data.0.delivery.order_day_of_week');

        // Смена расписания мимо Eloquent-событий — кеш листинга жив, delivery считается из БД
        $newDow = (now()->dayOfWeekIso - 1 + 3) % 7;
        DB::table('delivery_schedules')->where('id', $schedule->id)->update(['day_of_week' => $newDow]);

        $second = $this->getJson(self::PATH)->json('data.0.delivery.order_day_of_week');

        $this->assertSame($newDow, $second);
        $this->assertNotSame($first, $second);
    }

    public function test_delivery_uses_request_city_price(): void
    {
        $wheel = WheelProduct::factory()->create();

        $stockA = $this->createStock($wheel);
        $this->createCatalogPrice($stockA, $this->defaultCity, price: 100, deliveryMin: 2);
        $this->createCatalogPrice($stockA, $this->otherCity, price: 300, deliveryMin: 2);
        $this->createScheduleForToday($stockA->warehouse);

        $stockB = $this->createStock($wheel);
        $this->createCatalogPrice($stockB, $this->defaultCity, price: 200, deliveryMin: 5);
        $this->createCatalogPrice($stockB, $this->otherCity, price: 150, deliveryMin: 5);
        $this->createScheduleForToday($stockB->warehouse);

        $this->assertSame(2, $this->getJson(self::PATH)->json('data.0.delivery.delivery_min'));
        $this->assertSame(5, $this->getJson(self::PATH.'?city_id='.$this->otherCity->id)->json('data.0.delivery.delivery_min'));
    }

    private function createCity(string $name): City
    {
        return City::create(['region_id' => $this->region->id, 'name' => $name, 'sort' => 1]);
    }

    private function createScheduleForToday(Warehouse $warehouse, int $daysBefore = 2, int $daysAfter = 5): DeliverySchedule
    {
        return DeliverySchedule::create([
            'warehouse_id' => $warehouse->id,
            'day_of_week' => now()->dayOfWeekIso - 1,
            'cutoff_time' => '18:00',
            'days_before' => $daysBefore,
            'days_after' => $daysAfter,
        ]);
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
        ?int $deliveryMax = null,
    ): CatalogPrice {
        return CatalogPrice::create([
            'stock_id' => $stock->id,
            'city_id' => $city->id,
            'price' => $price,
            'delivery_min' => $deliveryMin,
            'delivery_max' => $deliveryMax ?? $deliveryMin,
        ]);
    }
}
