<?php

namespace Tests\Feature\Catalog;

use App\DTOs\Catalog\Tire\EuroLabel;
use App\Models\Catalog\Brand\Brand;
use App\Models\Catalog\Country\Country;
use App\Models\Catalog\Model\ProductModel;
use App\Models\Catalog\Tire\TireProduct;
use App\Models\Catalog\Warehouse\Stock;
use App\Models\Catalog\Warehouse\Warehouse;
use App\Models\Delivery\CatalogPrice;
use App\Models\Delivery\City;
use App\Models\Delivery\Region;
use App\Models\Image;
use App\Services\Cache\Catalog\TireListCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $this->region = Region::create(['code' => '74', 'name' => 'Челябинская область']);
        $this->defaultCity = $this->createCity('Челябинск');
        $this->otherCity = $this->createCity('Екатеринбург');
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
            ['id', 'name', 'slug', 'brand', 'model', 'width', 'profile', 'diameter', 'season', 'is_studded', 'euro_label', 'price', 'delivery_min', 'delivery_max', 'images'],
            array_keys($response->json('data.0')),
        );
        $this->assertSame($tire->id, $response->json('data.0.id'));
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

    public function test_aggregates_price_and_delivery_across_stocks(): void
    {
        $tire = TireProduct::factory()->create();
        $this->createCatalogPrice($this->createStock($tire), $this->defaultCity, price: 5000, deliveryMin: 2);
        $this->createCatalogPrice($this->createStock($tire), $this->defaultCity, price: 3000, deliveryMin: 5);

        $item = $this->getJson(self::PATH)->json('data.0');

        $this->assertEquals(3000.0, $item['price']);
        $this->assertSame(2, $item['delivery_min']);
        $this->assertSame(5, $item['delivery_max']);
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

        // Кеш-hit: 0 запросов БД и валидная структура (вложенные Resource не должны
        // попасть в кеш объектами — иначе __PHP_Incomplete_Class в ответе)
        $response = $this->getJson(self::PATH);

        $this->assertSame(0, count(DB::getQueryLog()));
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
