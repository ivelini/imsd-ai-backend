<?php

namespace Tests\Feature\Catalog;

use App\DTOs\Catalog\OriginInfo;
use App\DTOs\Catalog\Tire\EuroLabel;
use App\Models\Catalog\Brand\Brand;
use App\Models\Catalog\Country\Country;
use App\Models\Catalog\Model\ProductModel;
use App\Models\Catalog\Origin\ProductOrigin;
use App\Models\Catalog\Tire\TireProduct;
use App\Models\Catalog\Warehouse\Stock;
use App\Models\Catalog\Warehouse\Warehouse;
use App\Models\Delivery\CatalogPrice;
use App\Models\Delivery\City;
use App\Models\Delivery\DeliverySchedule;
use App\Models\Delivery\Region;
use App\Models\Image;
use App\Services\Cache\Catalog\TireListCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Пагинированный список шин каталога для города. */
class GetTireListTest extends TestCase
{
    use RefreshDatabase;

    private const PATH = '/api/catalog/tires';

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
        $tire = TireProduct::factory()->create();
        $this->createCatalogPrice($this->createStock($tire), $this->defaultCity);

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
            ['id', 'ean', 'name', 'slug', 'brand', 'model', 'origin', 'width', 'profile', 'diameter', 'season', 'is_studded', 'euro_label', 'price', 'images'],
            array_keys($response->json('data.0')),
        );
        $this->assertSame($tire->id, $response->json('data.0.id'));
    }

    public function test_origin_included_in_item(): void
    {
        $origin = ProductOrigin::create([
            'vendor' => new OriginInfo('Shandong Haohua Tire', '<p>Описание.</p>'),
            'manufacture_country' => new OriginInfo('100% Китай', null),
            'manufacture_year' => new OriginInfo('2024-2025', null),
        ]);

        $tire = TireProduct::factory()->create(['origin_id' => $origin->id]);
        $this->createCatalogPrice($this->createStock($tire), $this->defaultCity);

        $this->getJson(self::PATH)
            ->assertOk()
            ->assertJsonPath('data.0.origin.vendor.badge', 'Shandong Haohua Tire')
            ->assertJsonPath('data.0.origin.vendor.description', '<p>Описание.</p>')
            ->assertJsonPath('data.0.origin.manufacture_country.badge', '100% Китай')
            ->assertJsonPath('data.0.origin.manufacture_year.badge', '2024-2025');
    }

    public function test_origin_null_when_absent(): void
    {
        $tire = TireProduct::factory()->create();
        $this->createCatalogPrice($this->createStock($tire), $this->defaultCity);

        $this->getJson(self::PATH)
            ->assertOk()
            ->assertJsonPath('data.0.origin', null);
    }

    public function test_meta_seo_from_config_without_brand(): void
    {
        $tire = TireProduct::factory()->create();
        $this->createCatalogPrice($this->createStock($tire), $this->defaultCity);

        // Без brand — дефолтные мета из config/shop.php с подстановкой выбранного города
        $this->assertSame(
            ['title' => str_replace('{city}', 'в Челябинске', config('shop.seo.title')), 'description' => config('shop.seo.description')],
            $this->getJson(self::PATH)->json('meta.seo'),
        );
    }

    public function test_meta_seo_with_brand(): void
    {
        $brand = Brand::factory()->create(['name' => 'Nokian', 'slug' => 'nokian', 'type' => 'tire', 'description' => 'Финские шины']);
        $tire = TireProduct::factory()->create(['brand_id' => $brand->id]);
        $this->createCatalogPrice($this->createStock($tire), $this->defaultCity);

        $seo = $this->getJson(self::PATH.'?brand=nokian')->json('meta.seo');

        $this->assertSame(
            ['title' => 'Шины Nokian в Челябинске', 'description' => 'Финские шины'],
            $seo,
        );
    }

    public function test_returns_model_reference(): void
    {
        $brand = Brand::factory()->create();
        $model = ProductModel::factory()->create(['brand_id' => $brand->id]);
        $tire = TireProduct::factory()->create(['brand_id' => $brand->id, 'model_id' => $model->id]);
        $this->createCatalogPrice($this->createStock($tire), $this->defaultCity);

        $modelData = $this->getJson(self::PATH)->json('data.0.model');

        $this->assertSame(
            ['id' => $model->id, 'name' => $model->name, 'slug' => $model->slug],
            $modelData,
        );
    }

    public function test_model_null_when_no_model(): void
    {
        $tire = TireProduct::factory()->create();
        $this->createCatalogPrice($this->createStock($tire), $this->defaultCity);

        $this->assertNull($this->getJson(self::PATH)->json('data.0.model'));
    }

    public function test_serializes_euro_label_object_or_null(): void
    {
        $withLabel = TireProduct::factory()->create(['euro_label' => new EuroLabel('D', 'C', '71')]);
        $this->createCatalogPrice($this->createStock($withLabel), $this->defaultCity);

        $withoutLabel = TireProduct::factory()->create();
        $this->createCatalogPrice($this->createStock($withoutLabel), $this->defaultCity);

        // Сортировка по id desc: последняя созданная (без лейбла) — первая в списке
        $items = $this->getJson(self::PATH)->json('data');

        $this->assertNull($items[0]['euro_label']);
        $this->assertSame(
            ['rollingResistance' => 'D', 'wetGrip' => 'C', 'noiseEmission' => '71'],
            $items[1]['euro_label'],
        );
    }

    public function test_returns_season_reference(): void
    {
        $tire = TireProduct::factory()->create(['season' => 'winter']);
        $this->createCatalogPrice($this->createStock($tire), $this->defaultCity);

        $season = $this->getJson(self::PATH)->json('data.0.season');

        $this->assertSame(['label' => 'Зимняя', 'value' => 'winter'], $season);
    }

    public function test_default_per_page_is_48(): void
    {
        TireProduct::factory()->count(50)->create()->each(function (TireProduct $tire): void {
            $this->createCatalogPrice($this->createStock($tire), $this->defaultCity);
        });

        $response = $this->getJson(self::PATH);

        $this->assertCount(48, $response->json('data'));
        $this->assertSame(50, $response->json('meta.total'));
        $this->assertSame(2, $response->json('meta.last_page'));
    }

    public function test_excludes_unpublished(): void
    {
        $published = TireProduct::factory()->create();
        $this->createCatalogPrice($this->createStock($published), $this->defaultCity);

        $unpublished = TireProduct::factory()->create(['is_published' => false]);
        $this->createCatalogPrice($this->createStock($unpublished), $this->defaultCity);

        $response = $this->getJson(self::PATH);

        $this->assertSame(1, $response->json('meta.total'));
        $this->assertSame($published->id, $response->json('data.0.id'));
    }

    public function test_excludes_out_of_stock(): void
    {
        $tire = TireProduct::factory()->create();
        $this->createCatalogPrice($this->createStock($tire, ['quantity' => 0]), $this->defaultCity);

        $response = $this->getJson(self::PATH);

        $this->assertSame(0, $response->json('meta.total'));
        $this->assertSame([], $response->json('data'));
    }

    public function test_excludes_products_without_city_price(): void
    {
        $tire = TireProduct::factory()->create();
        $this->createStock($tire); // сток есть, цены города нет

        $response = $this->getJson(self::PATH);

        $this->assertSame(0, $response->json('meta.total'));
    }

    public function test_uses_default_city_price(): void
    {
        $tire = TireProduct::factory()->create();
        $stock = $this->createStock($tire);
        $this->createCatalogPrice($stock, $this->defaultCity, price: 2500);
        $this->createCatalogPrice($stock, $this->otherCity, price: 1);

        $data = $this->getJson(self::PATH)->json('data');

        $this->assertEquals(2500.0, $data[0]['price']);
    }

    public function test_uses_requested_city_and_excludes_unpriced_there(): void
    {
        $tireA = TireProduct::factory()->create();
        $this->createCatalogPrice($this->createStock($tireA), $this->otherCity, price: 1500);

        $tireB = TireProduct::factory()->create();
        $this->createCatalogPrice($this->createStock($tireB), $this->defaultCity, price: 500);

        $data = $this->getJson(self::PATH.'?city_id='.$this->otherCity->id)->json('data');

        $this->assertCount(1, $data);
        $this->assertSame($tireA->id, $data[0]['id']);
        $this->assertEquals(1500.0, $data[0]['price']);
    }

    public function test_accepts_city_slug(): void
    {
        $chelyabinsk = City::create([
            'region_id' => $this->region->id,
            'name' => 'Челябинск',
            'slug' => 'chelyabinsk',
            'sort' => 1,
        ]);

        $tireA = TireProduct::factory()->create();
        $this->createCatalogPrice($this->createStock($tireA), $chelyabinsk, price: 1500);

        $tireB = TireProduct::factory()->create();
        $this->createCatalogPrice($this->createStock($tireB), $this->otherCity, price: 500);

        $data = $this->getJson(self::PATH.'?city=chelyabinsk')->json('data');

        // Цена и сео города из слага, а не дефолтного
        $this->assertCount(1, $data);
        $this->assertSame($tireA->id, $data[0]['id']);
        $this->assertEquals(1500.0, $data[0]['price']);
        $this->assertStringContainsString('в Челябинске', $this->getJson(self::PATH.'?city=chelyabinsk')->json('meta.seo.title'));
    }

    public function test_unknown_city_slug_falls_back_to_default(): void
    {
        $tire = TireProduct::factory()->create();
        $this->createCatalogPrice($this->createStock($tire), $this->defaultCity, price: 2500);

        $response = $this->getJson(self::PATH.'?city=unknown-slug');

        // Несуществующий слаг — не 422, работает дефолтный город
        $response->assertOk();
        $this->assertSame(1, $response->json('meta.total'));
        $this->assertEquals(2500.0, $response->json('data.0.price'));
    }

    public function test_width_filter(): void
    {
        $narrow = TireProduct::factory()->create(['width' => 205]);
        $this->createCatalogPrice($this->createStock($narrow), $this->defaultCity);

        $wide = TireProduct::factory()->create(['width' => 215]);
        $this->createCatalogPrice($this->createStock($wide), $this->defaultCity);

        $data = $this->getJson(self::PATH.'?width[]=205')->json('data');

        $this->assertCount(1, $data);
        $this->assertSame($narrow->id, $data[0]['id']);
    }

    public function test_season_filter(): void
    {
        $winter = TireProduct::factory()->create(['season' => 'winter']);
        $this->createCatalogPrice($this->createStock($winter), $this->defaultCity);

        $summer = TireProduct::factory()->create(['season' => 'summer']);
        $this->createCatalogPrice($this->createStock($summer), $this->defaultCity);

        $data = $this->getJson(self::PATH.'?season=winter')->json('data');

        $this->assertCount(1, $data);
        $this->assertSame(['label' => 'Зимняя', 'value' => 'winter'], $data[0]['season']);
    }

    public function test_studded_filter(): void
    {
        $studded = TireProduct::factory()->create(['is_studded' => true]);
        $this->createCatalogPrice($this->createStock($studded), $this->defaultCity);

        $plain = TireProduct::factory()->create(['is_studded' => false]);
        $this->createCatalogPrice($this->createStock($plain), $this->defaultCity);

        $data = $this->getJson(self::PATH.'?studded=not_studded')->json('data');

        $this->assertCount(1, $data);
        $this->assertFalse($data[0]['is_studded']);
    }

    public function test_brand_slug_filter(): void
    {
        $nokian = Brand::factory()->create(['name' => 'Nokian', 'slug' => 'nokian']);
        $kama = Brand::factory()->create(['name' => 'Kama', 'slug' => 'kama']);

        $tireA = TireProduct::factory()->create(['brand_id' => $nokian->id]);
        $this->createCatalogPrice($this->createStock($tireA), $this->defaultCity);

        $tireB = TireProduct::factory()->create(['brand_id' => $kama->id]);
        $this->createCatalogPrice($this->createStock($tireB), $this->defaultCity);

        $data = $this->getJson(self::PATH.'?brand=nokian')->json('data');

        $this->assertCount(1, $data);
        $this->assertSame(
            ['id' => $nokian->id, 'name' => 'Nokian', 'slug' => $nokian->slug],
            $data[0]['brand'],
        );
    }

    public function test_country_slug_filter(): void
    {
        $finland = Country::create(['name' => 'Финляндия', 'slug' => 'finland']);
        $china = Country::create(['name' => 'Китай', 'slug' => 'china']);

        $tireA = TireProduct::factory()->create(['country_id' => $finland->id]);
        $this->createCatalogPrice($this->createStock($tireA), $this->defaultCity);

        $tireB = TireProduct::factory()->create(['country_id' => $china->id]);
        $this->createCatalogPrice($this->createStock($tireB), $this->defaultCity);

        $data = $this->getJson(self::PATH.'?country=finland')->json('data');

        $this->assertCount(1, $data);
        $this->assertSame($tireA->id, $data[0]['id']);
    }

    public function test_delivery_bucket_filter(): void
    {
        $fast = TireProduct::factory()->create();
        $this->createCatalogPrice($this->createStock($fast), $this->defaultCity, deliveryMin: 2);

        $slow = TireProduct::factory()->create();
        $this->createCatalogPrice($this->createStock($slow), $this->defaultCity, deliveryMin: 6);

        $data = $this->getJson(self::PATH.'?delivery[]=between1and3days')->json('data');

        $this->assertCount(1, $data);
        $this->assertSame($fast->id, $data[0]['id']);
    }

    public function test_delivery_multiple_buckets(): void
    {
        $today = TireProduct::factory()->create();
        $this->createCatalogPrice($this->createStock($today), $this->defaultCity, deliveryMin: 0);

        $slow = TireProduct::factory()->create();
        $this->createCatalogPrice($this->createStock($slow), $this->defaultCity, deliveryMin: 6);

        $data = $this->getJson(self::PATH.'?delivery[]=today&delivery[]=between1and3days')->json('data');

        // Товар попадает, если min_days входит в любой из выбранных бакетов
        $this->assertCount(1, $data);
        $this->assertSame($today->id, $data[0]['id']);
    }

    public function test_price_range_filter(): void
    {
        $cheap = TireProduct::factory()->create();
        $this->createCatalogPrice($this->createStock($cheap), $this->defaultCity, price: 1000);

        $expensive = TireProduct::factory()->create();
        $this->createCatalogPrice($this->createStock($expensive), $this->defaultCity, price: 3500);

        $data = $this->getJson(self::PATH.'?price_min=3000&price_max=4000')->json('data');

        $this->assertCount(1, $data);
        $this->assertSame($expensive->id, $data[0]['id']);
    }

    public function test_price_is_min_across_stocks(): void
    {
        $tire = TireProduct::factory()->create();
        $this->createCatalogPrice($this->createStock($tire), $this->defaultCity, price: 5000, deliveryMin: 2);
        $this->createCatalogPrice($this->createStock($tire), $this->defaultCity, price: 3000, deliveryMin: 5);

        $item = $this->getJson(self::PATH)->json('data.0');

        // Цена — min по стокам; delivery — от склада с минимальной ценой, но без расписания → блок отсутствует
        $this->assertEquals(3000.0, $item['price']);
        $this->assertArrayNotHasKey('delivery', $item);
    }

    public function test_sort_by_price_asc(): void
    {
        $expensive = TireProduct::factory()->create();
        $this->createCatalogPrice($this->createStock($expensive), $this->defaultCity, price: 3000);

        $cheap = TireProduct::factory()->create();
        $this->createCatalogPrice($this->createStock($cheap), $this->defaultCity, price: 1000);

        $data = $this->getJson(self::PATH.'?sort_by=price&sort_dir=asc')->json('data');

        $this->assertSame($cheap->id, $data[0]['id']);
        $this->assertSame($expensive->id, $data[1]['id']);
    }

    public function test_sort_by_price_desc_uses_city_price(): void
    {
        $tireA = TireProduct::factory()->create();
        $stockA = $this->createStock($tireA);
        $this->createCatalogPrice($stockA, $this->defaultCity, price: 1000);
        $this->createCatalogPrice($stockA, $this->otherCity, price: 9000);

        $tireB = TireProduct::factory()->create();
        $stockB = $this->createStock($tireB);
        $this->createCatalogPrice($stockB, $this->defaultCity, price: 5000);
        $this->createCatalogPrice($stockB, $this->otherCity, price: 500);

        $data = $this->getJson(self::PATH.'?sort_by=price&sort_dir=desc&city_id='.$this->otherCity->id)->json('data');

        $this->assertSame($tireA->id, $data[0]['id']);
        $this->assertEquals(9000.0, $data[0]['price']);
    }

    public function test_default_sort_is_id_desc(): void
    {
        $first = TireProduct::factory()->create();
        $this->createCatalogPrice($this->createStock($first), $this->defaultCity);

        $second = TireProduct::factory()->create();
        $this->createCatalogPrice($this->createStock($second), $this->defaultCity);

        $data = $this->getJson(self::PATH)->json('data');

        $this->assertSame($second->id, $data[0]['id']);
        $this->assertSame($first->id, $data[1]['id']);
    }

    public function test_invalid_sort_and_pagination_rejected(): void
    {
        $this->getJson(self::PATH.'?sort_by=name')->assertStatus(422);
        $this->getJson(self::PATH.'?per_page=5')->assertStatus(422);
        $this->getJson(self::PATH.'?per_page=101')->assertStatus(422);
        $this->getJson(self::PATH.'?page=0')->assertStatus(422);
        $this->getJson(self::PATH.'?delivery=between1and3days')->assertStatus(422);
    }

    public function test_out_of_range_page_returns_empty(): void
    {
        $tire = TireProduct::factory()->create();
        $this->createCatalogPrice($this->createStock($tire), $this->defaultCity);

        $response = $this->getJson(self::PATH.'?page=99');

        $response->assertOk();
        $this->assertSame([], $response->json('data'));
        $this->assertSame(1, $response->json('meta.total'));
    }

    public function test_image_is_main_url(): void
    {
        $tire = TireProduct::factory()->create();
        $this->createCatalogPrice($this->createStock($tire), $this->defaultCity);
        $this->createImage($tire, 'tires/first.jpg', isMain: false, sort: 0);
        $this->createImage($tire, 'tires/main.jpg', isMain: true, sort: 5);

        $images = $this->getJson(self::PATH)->json('data.0.images');

        $this->assertSame(Storage::url('tires/main.jpg'), $images[0]['url']);
    }

    public function test_image_null_when_no_images(): void
    {
        $tire = TireProduct::factory()->create();
        $this->createCatalogPrice($this->createStock($tire), $this->defaultCity);

        $images = $this->getJson(self::PATH)->json('data.0.images');

        $this->assertSame([], $images);
    }

    public function test_response_is_cached(): void
    {
        $brand = Brand::factory()->create();
        $model = ProductModel::factory()->create(['brand_id' => $brand->id]);
        $tire = TireProduct::factory()->create(['name' => 'Кеш-шина', 'brand_id' => $brand->id, 'model_id' => $model->id]);
        $this->createCatalogPrice($this->createStock($tire), $this->defaultCity);

        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->getJson(self::PATH)->assertOk();
        DB::flushQueryLog();

        // Кеш-hit: тяжёлый листинг (товары/фильтры) не пересчитывается — delivery считается
        // вне кеша лёгкими запросами (город, выбор склада, расписания)
        $response = $this->getJson(self::PATH);

        $this->assertStringNotContainsString('from "tire_products"', collect(DB::getQueryLog())->implode('query'));
        $response->assertOk()
            ->assertJsonPath('data.0.name', 'Кеш-шина')
            ->assertJsonPath('data.0.brand.id', $tire->brand->id)
            ->assertJsonPath('data.0.model.id', $model->id)
            ->assertJsonPath('data.0.season.value', 'summer');
    }

    public function test_cache_key_includes_filters(): void
    {
        $narrow = TireProduct::factory()->create(['width' => 205]);
        $this->createCatalogPrice($this->createStock($narrow), $this->defaultCity);

        $wide = TireProduct::factory()->create(['width' => 215]);
        $this->createCatalogPrice($this->createStock($wide), $this->defaultCity);

        $narrowData = $this->getJson(self::PATH.'?width[]=205')->json('data');
        $this->assertCount(1, $narrowData);

        $fullData = $this->getJson(self::PATH)->json('data');
        $this->assertCount(2, $fullData);
    }

    public function test_forget_invalidates_all_variants(): void
    {
        $tire = TireProduct::factory()->create();
        $price = $this->createCatalogPrice($this->createStock($tire), $this->defaultCity, price: 1000);

        $this->getJson(self::PATH)->assertOk();

        $price->update(['price' => 9999]);

        app(TireListCacheService::class)->forget();

        $data = $this->getJson(self::PATH)->json('data');

        $this->assertEquals(9999.0, $data[0]['price']);
    }

    public function test_invalid_city_id_rejected(): void
    {
        $this->getJson(self::PATH.'?city_id=999999')->assertStatus(422);
    }

    public function test_profile_and_diameter_filters(): void
    {
        $tireA = TireProduct::factory()->create(['profile' => 60, 'diameter' => '16']);
        $this->createCatalogPrice($this->createStock($tireA), $this->defaultCity);

        $tireB = TireProduct::factory()->create(['profile' => 55, 'diameter' => '17']);
        $this->createCatalogPrice($this->createStock($tireB), $this->defaultCity);

        $data = $this->getJson(self::PATH.'?profile[]=60&diameter[]=16')->json('data');

        $this->assertCount(1, $data);
        $this->assertSame($tireA->id, $data[0]['id']);
    }

    public function test_delivery_block_from_cheapest_warehouse_with_min_quantity(): void
    {
        // Дешёвый склад с quantity < 4 не участвует: delivery от склада с ценой 100 (qty 10)
        $tire = TireProduct::factory()->create();
        $cheapLowQty = $this->createStock($tire, ['quantity' => 2, 'price' => 90]);
        $this->createCatalogPrice($cheapLowQty, $this->defaultCity, price: 90, deliveryMin: 1);
        $selected = $this->createStock($tire, ['quantity' => 10, 'price' => 100]);
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
        $tire = TireProduct::factory()->create();
        $below = $this->createStock($tire, ['quantity' => 3, 'price' => 100]);
        $this->createCatalogPrice($below, $this->defaultCity, price: 100, deliveryMin: 1, deliveryMax: 2);
        $this->createScheduleForToday($below->warehouse);

        $selected = $this->createStock($tire, ['quantity' => 6, 'price' => 110]);
        $this->createCatalogPrice($selected, $this->defaultCity, price: 110, deliveryMin: 5, deliveryMax: 7);
        $this->createScheduleForToday($selected->warehouse);

        $delivery = $this->getJson(self::PATH)->json('data.0.delivery');

        $this->assertSame(5, $delivery['delivery_min']);
        $this->assertSame(7, $delivery['delivery_max']);
    }

    public function test_delivery_absent_without_warehouse_min_quantity(): void
    {
        $tire = TireProduct::factory()->create();
        $this->createCatalogPrice($this->createStock($tire, ['quantity' => 3]), $this->defaultCity);

        $item = $this->getJson(self::PATH)->json('data.0');

        $this->assertArrayNotHasKey('delivery', $item);
    }

    public function test_delivery_absent_when_warehouse_has_no_schedule(): void
    {
        $tire = TireProduct::factory()->create();
        $this->createCatalogPrice($this->createStock($tire, ['quantity' => 5]), $this->defaultCity);

        $item = $this->getJson(self::PATH)->json('data.0');

        $this->assertArrayNotHasKey('delivery', $item);
    }

    public function test_delivery_recalculated_on_cache_hit(): void
    {
        $tire = TireProduct::factory()->create();
        $stock = $this->createStock($tire);
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
        $tire = TireProduct::factory()->create();

        $stockA = $this->createStock($tire);
        $this->createCatalogPrice($stockA, $this->defaultCity, price: 100, deliveryMin: 2);
        $this->createCatalogPrice($stockA, $this->otherCity, price: 300, deliveryMin: 2);
        $this->createScheduleForToday($stockA->warehouse);

        $stockB = $this->createStock($tire);
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

    private function createImage(TireProduct $tire, string $path, bool $isMain, int $sort): Image
    {
        return Image::create([
            'imageable_type' => $tire->getMorphClass(),
            'imageable_id' => $tire->id,
            'path' => $path,
            'is_main' => $isMain,
            'sort' => $sort,
        ]);
    }
}
