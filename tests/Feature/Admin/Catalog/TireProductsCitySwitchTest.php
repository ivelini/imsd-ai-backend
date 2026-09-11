<?php

namespace Tests\Feature\Admin\Catalog;

use App\Filament\Clusters\Catalog\Resources\TireProducts\Pages\ListTireProducts;
use App\Models\Catalog\Tire\TireProduct;
use App\Models\Catalog\Warehouse\Stock;
use App\Models\Catalog\Warehouse\Warehouse;
use App\Models\Delivery\CatalogPrice;
use App\Models\Delivery\City;
use App\Models\Delivery\Region;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesAdmin;
use Tests\Concerns\CreatesCity;
use Tests\TestCase;

/**
 * Смена города на листинге шин.
 *
 * Конфигурация таблицы кешируется в фазе гидратации Livewire, до применения обновлённых свойств,
 * поэтому без пересборки в таблицу попадает предыдущий город (page/ListTireProducts::updatedCityId).
 */
class TireProductsCitySwitchTest extends TestCase
{
    use CreatesAdmin, CreatesCity, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->createAdmin(), 'admin');

        // Тест без HTTP-запроса: middleware панели не выполняется, панель задаётся явно.
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_switching_city_rebuilds_table_for_new_city(): void
    {
        $chelyabinsk = $this->createNamedCity('Челябинск');
        $ufa = $this->createNamedCity('Уфа');
        $stock = $this->createStockWithSchedule(price: 12_000);
        $this->createCityPrice($stock, $chelyabinsk, price: 12_500);
        $this->createCityPrice($stock, $ufa, price: 12_800);

        Livewire::test(ListTireProducts::class)
            ->set('cityId', $chelyabinsk->id)
            ->assertSee('Цена в городе (Челябинск)')
            ->assertSee('12 500,00 ₽')
            ->set('cityId', $ufa->id)
            ->assertSee('Цена в городе (Уфа)')
            ->assertSee('12 800,00 ₽')
            ->assertDontSee('12 500,00 ₽');
    }

    private function createNamedCity(string $name): City
    {
        $region = Region::firstOrCreate(['code' => '74'], ['name' => 'Челябинская область']);

        return City::create(['region_id' => $region->id, 'name' => $name, 'sort' => 1]);
    }

    /** Остаток на складе «Основной» с расписанием отгрузки — срок доставки считается. */
    private function createStockWithSchedule(float $price): Stock
    {
        $warehouse = Warehouse::factory()->create(['name' => 'Основной']);
        $this->createScheduleForToday($warehouse, daysBefore: 3, daysAfter: 6);

        $tire = TireProduct::factory()->create();

        return Stock::create([
            'stockable_type' => $tire->getMorphClass(),
            'stockable_id' => $tire->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 5,
            'price' => $price,
        ]);
    }

    private function createCityPrice(Stock $stock, City $city, float $price): void
    {
        CatalogPrice::create([
            'stock_id' => $stock->id,
            'city_id' => $city->id,
            'price' => $price,
            'base_price' => $price,
        ]);
    }
}
