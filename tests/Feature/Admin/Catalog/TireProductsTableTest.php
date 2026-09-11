<?php

namespace Tests\Feature\Admin\Catalog;

use App\Models\Catalog\Tire\TireProduct;
use App\Models\Catalog\Warehouse\Stock;
use App\Models\Catalog\Warehouse\Warehouse;
use App\Models\Delivery\CatalogPrice;
use App\Models\Delivery\City;
use App\Models\Delivery\CityDeliveryTime;
use App\Models\Delivery\Region;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\CreatesAdmin;
use Tests\Concerns\CreatesCity;
use Tests\TestCase;

/** Список шин: колонка «Склады» и цена/срок/наценка выбранного города. */
class TireProductsTableTest extends TestCase
{
    use CreatesAdmin, CreatesCity, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->createAdmin(), 'admin');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_stocks_column_shows_warehouse_quantity_and_price(): void
    {
        $tire = TireProduct::factory()->create();
        $this->createStock($tire, 'Основной', quantity: 5, price: 1000);
        $this->createStock($tire, 'Резерв', quantity: 0, price: null);

        $this->get('/panel/catalog/tire-products')
            ->assertOk()
            ->assertSeeText('Основной — 5 шт — 1 000,00 ₽')
            ->assertSeeText('Резерв — 0 шт')
            ->assertDontSeeText('Резерв — 0 шт —');
    }

    public function test_stocks_column_placeholder_without_stocks(): void
    {
        TireProduct::factory()->create();

        $this->get('/panel/catalog/tire-products')
            ->assertOk()
            ->assertSeeText('Нет остатков');
    }

    // ─── Цена, срок и наценка выбранного города ───────────────────────────

    public function test_city_column_shows_price_exact_days_and_markup(): void
    {
        $this->freezeTimeOfDay(10);

        $city = $this->createCityWithDelivery('Уфа', deliveryDays: 2);
        $stock = $this->createStockWithSchedule(quantity: 5, price: 12_500);
        $this->createCityPrice($stock, $city, price: 12_800);

        $this->get($this->listUrl($city))
            ->assertOk()
            ->assertSeeText('12 800,00 ₽ — 5 дн. (наценка 300,00 ₽)');
    }

    public function test_city_column_days_use_next_shipment_after_cutoff(): void
    {
        $this->freezeTimeOfDay(19);

        $city = $this->createCityWithDelivery('Уфа', deliveryDays: 2);
        $stock = $this->createStockWithSchedule(quantity: 5, price: 12_500);
        $this->createCityPrice($stock, $city, price: 12_800);

        $this->get($this->listUrl($city))
            ->assertOk()
            ->assertSeeText('8 дн.');
    }

    public function test_city_column_placeholder_without_city_price(): void
    {
        $this->freezeTimeOfDay(10);

        $city = $this->createCityWithDelivery('Уфа', deliveryDays: 2);
        $this->createStockWithSchedule(quantity: 5, price: 12_500);

        $this->get($this->listUrl($city))
            ->assertOk()
            ->assertSeeText('Нет цены')
            ->assertDontSeeText('5 дн.');
    }

    public function test_city_column_shows_price_when_no_warehouse_has_schedule(): void
    {
        $this->freezeTimeOfDay(10);

        $city = $this->createCityWithDelivery('Уфа', deliveryDays: 2);
        // Расписания отгрузки нет — срок посчитать не из чего, но цена города есть
        $stock = $this->createStock(TireProduct::factory()->create(), 'Основной', quantity: 5, price: 12_500);
        $this->createCityPrice($stock, $city, price: 12_800);

        $this->get($this->listUrl($city))
            ->assertOk()
            ->assertSeeText('12 800,00 ₽')
            ->assertDontSeeText('дн.');
    }

    public function test_city_is_taken_from_query_string(): void
    {
        $this->freezeTimeOfDay(10);

        $chelyabinsk = $this->createCityWithDelivery('Челябинск', deliveryDays: 0);
        $ufa = $this->createCityWithDelivery('Уфа', deliveryDays: 2);
        $stock = $this->createStockWithSchedule(quantity: 5, price: 12_000);
        $this->createCityPrice($stock, $chelyabinsk, price: 12_500);
        $this->createCityPrice($stock, $ufa, price: 12_800);

        $this->get($this->listUrl($ufa))
            ->assertOk()
            ->assertSeeText('12 800,00 ₽')
            ->assertDontSeeText('12 500,00 ₽');
    }

    public function test_city_defaults_to_config_city_and_renders_select(): void
    {
        $this->freezeTimeOfDay(10);

        $chelyabinsk = $this->createCityWithDelivery('Челябинск', deliveryDays: 0);
        $this->createCityWithDelivery('Уфа', deliveryDays: 2);
        $stock = $this->createStockWithSchedule(quantity: 5, price: 12_500);
        $this->createCityPrice($stock, $chelyabinsk, price: 12_500);

        $this->get($this->listUrl())
            ->assertOk()
            ->assertSeeText('Цена в городе (Челябинск)')
            // Челябинск без дней города: срок — только отгрузка со склада, наценки нет
            ->assertSeeText('12 500,00 ₽ — 3 дн.')
            ->assertDontSeeText('(наценка')
            // Опции селекта лежат в Alpine-атрибуте, текстом их не увидеть
            ->assertSee('Уфа')
            // Селект должен управлять свойством страницы, иначе выбор города ничего не меняет
            ->assertSee("entangle('cityId'", false);
    }

    private function listUrl(?City $city = null): string
    {
        return '/panel/catalog/tire-products'.($city !== null ? "?cityId={$city->id}" : '');
    }

    /** Момент запроса внутри дня: утро — до отсечки, вечер — после. */
    private function freezeTimeOfDay(int $hour): void
    {
        Carbon::setTestNow(now()->startOfDay()->addHours($hour));
    }

    private function createCityWithDelivery(string $name, int $deliveryDays): City
    {
        $region = Region::firstOrCreate(['code' => '74'], ['name' => 'Челябинская область']);

        $city = City::create(['region_id' => $region->id, 'name' => $name, 'sort' => 1]);

        CityDeliveryTime::create(['city_id' => $city->id, 'delivery_days' => $deliveryDays]);

        return $city;
    }

    /** Остаток на складе «Основной» + расписание: сегодня, до отсечки 3 дня, после — 6. */
    private function createStockWithSchedule(int $quantity, float $price): Stock
    {
        $stock = $this->createStock(TireProduct::factory()->create(), 'Основной', quantity: $quantity, price: $price);
        $this->createScheduleForToday($stock->warehouse, daysBefore: 3, daysAfter: 6);

        return $stock;
    }

    /** Цена города для остатка; без basePrice наценки нет. */
    private function createCityPrice(Stock $stock, City $city, float $price, ?float $basePrice = null): void
    {
        CatalogPrice::create([
            'stock_id' => $stock->id,
            'city_id' => $city->id,
            'price' => $price,
            'base_price' => $basePrice ?? $price,
        ]);
    }

    private function createStock(TireProduct $tire, string $warehouseName, int $quantity, ?float $price): Stock
    {
        $warehouse = Warehouse::factory()->create(['name' => $warehouseName]);

        return Stock::create([
            'stockable_type' => $tire->getMorphClass(),
            'stockable_id' => $tire->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => $quantity,
            'price' => $price,
        ]);
    }
}
