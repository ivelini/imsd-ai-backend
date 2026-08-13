<?php

namespace Tests\Feature\Catalog;

use App\Actions\Catalog\PopulateCatalogPrices;
use App\DTOs\Catalog\PopulateCatalogPricesInput;
use App\Models\Catalog\MarkupRule\WarehouseMarkupRule;
use App\Models\Catalog\Tire\TireProduct;
use App\Models\Catalog\Warehouse\Stock;
use App\Models\Catalog\Warehouse\Warehouse;
use App\Models\Delivery\CatalogPrice;
use App\Models\Delivery\City;
use App\Models\Delivery\CityDeliveryTime;
use App\Models\Delivery\CityPriceRule;
use App\Models\Delivery\DeliverySchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCity;
use Tests\TestCase;

/** Массовый пересчёт catalog_prices с учётом наценок складов. */
class PopulateCatalogPricesTest extends TestCase
{
    use CreatesCity, RefreshDatabase;

    public function test_execute_populates_prices_with_markup(): void
    {
        $warehouse = Warehouse::factory()->create();
        WarehouseMarkupRule::create([
            'warehouse_id' => $warehouse->id,
            'price_from' => 0,
            'price_to' => 200,
            'coefficient' => 1.5,
        ]);

        $tire = TireProduct::factory()->create();
        Stock::create([
            'stockable_type' => $tire->getMorphClass(),
            'stockable_id' => $tire->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 5,
            'purchase_price' => 100,
        ]);

        $city = $this->createCity();

        app(PopulateCatalogPrices::class)->execute(new PopulateCatalogPricesInput);

        $stockId = Stock::firstOrFail()->id;
        $this->assertSame(
            150.0,
            CatalogPrice::where('stock_id', $stockId)
                ->where('city_id', $city->id)
                ->value('price'),
        );
    }

    public function test_selective_recalc_updates_only_given_stocks(): void
    {
        $warehouseA = Warehouse::factory()->create();
        $warehouseB = Warehouse::factory()->create();
        $stockA = $this->createStock($warehouseA, 100);
        $stockB = $this->createStock($warehouseB, 200);
        $city = $this->createCity();

        app(PopulateCatalogPrices::class)->execute(new PopulateCatalogPricesInput);

        $this->assertSame(100.0, $this->priceOf($stockA, $city));
        $this->assertSame(200.0, $this->priceOf($stockB, $city));

        $stockA->update(['purchase_price' => 300]);
        $stockB->update(['purchase_price' => 400]);

        app(PopulateCatalogPrices::class)->execute(new PopulateCatalogPricesInput(stockIds: [$stockA->id]));

        $this->assertSame(300.0, $this->priceOf($stockA, $city));
        $this->assertSame(200.0, $this->priceOf($stockB, $city));
    }

    public function test_delivery_min_max_filled_from_schedule_envelope_plus_city(): void
    {
        $warehouse = Warehouse::factory()->create();
        $this->createWeeklySchedule($warehouse);
        $city = $this->createCity();
        CityDeliveryTime::create(['city_id' => $city->id, 'delivery_days' => 1]);
        $stock = $this->createStock($warehouse, 100);

        app(PopulateCatalogPrices::class)->execute(new PopulateCatalogPricesInput);

        $catalogPrice = CatalogPrice::where('stock_id', $stock->id)->where('city_id', $city->id)->firstOrFail();
        $this->assertSame(2, $catalogPrice->delivery_min);
        $this->assertSame(4, $catalogPrice->delivery_max);
    }

    public function test_delivery_null_when_city_has_no_delivery_time(): void
    {
        $warehouse = Warehouse::factory()->create();
        $this->createWeeklySchedule($warehouse);
        WarehouseMarkupRule::create([
            'warehouse_id' => $warehouse->id, 'price_from' => 0, 'price_to' => 500, 'coefficient' => 1.5,
        ]);
        $city = $this->createCity();
        $stock = $this->createStock($warehouse, 100);

        app(PopulateCatalogPrices::class)->execute(new PopulateCatalogPricesInput);

        $catalogPrice = CatalogPrice::where('stock_id', $stock->id)->where('city_id', $city->id)->firstOrFail();
        $this->assertNull($catalogPrice->delivery_min);
        $this->assertNull($catalogPrice->delivery_max);
        $this->assertSame(150.0, $catalogPrice->price);
    }

    public function test_delivery_null_when_warehouse_has_no_schedule(): void
    {
        $warehouse = Warehouse::factory()->create();
        $city = $this->createCity();
        CityDeliveryTime::create(['city_id' => $city->id, 'delivery_days' => 1]);
        $stock = $this->createStock($warehouse, 100);

        app(PopulateCatalogPrices::class)->execute(new PopulateCatalogPricesInput);

        $catalogPrice = CatalogPrice::where('stock_id', $stock->id)->where('city_id', $city->id)->firstOrFail();
        $this->assertNull($catalogPrice->delivery_min);
        $this->assertNull($catalogPrice->delivery_max);
    }

    public function test_delivery_values_are_overwritten_on_recalc(): void
    {
        $warehouse = Warehouse::factory()->create();
        $this->createWeeklySchedule($warehouse);
        $city = $this->createCity();
        $cityTime = CityDeliveryTime::create(['city_id' => $city->id, 'delivery_days' => 1]);
        $stock = $this->createStock($warehouse, 100);

        app(PopulateCatalogPrices::class)->execute(new PopulateCatalogPricesInput);
        $this->assertSame(2, CatalogPrice::where('stock_id', $stock->id)->where('city_id', $city->id)->value('delivery_min'));

        $cityTime->update(['delivery_days' => 2]);

        app(PopulateCatalogPrices::class)->execute(new PopulateCatalogPricesInput);

        $catalogPrice = CatalogPrice::where('stock_id', $stock->id)->where('city_id', $city->id)->firstOrFail();
        $this->assertSame(3, $catalogPrice->delivery_min);
        $this->assertSame(5, $catalogPrice->delivery_max);
    }

    public function test_price_includes_city_markup(): void
    {
        $warehouse = Warehouse::factory()->create();
        WarehouseMarkupRule::create([
            'warehouse_id' => $warehouse->id, 'price_from' => 0, 'price_to' => 500, 'coefficient' => 1.5,
        ]);
        $city = $this->createCity();
        CityPriceRule::create(['city_id' => $city->id, 'price_from' => 0, 'price_to' => 200, 'markup' => 50]);
        $stock = $this->createStock($warehouse, 100);

        app(PopulateCatalogPrices::class)->execute(new PopulateCatalogPricesInput);

        $this->assertSame(200.0, $this->priceOf($stock, $city));
    }

    public function test_city_markup_matched_against_final_price(): void
    {
        $warehouse = Warehouse::factory()->create();
        WarehouseMarkupRule::create([
            'warehouse_id' => $warehouse->id, 'price_from' => 0, 'price_to' => 500, 'coefficient' => 1.5,
        ]);
        $city = $this->createCity();
        // Покрывает 150 (финальная), но не 100 (закупочная)
        CityPriceRule::create(['city_id' => $city->id, 'price_from' => 120, 'price_to' => 170, 'markup' => 50]);
        $stock = $this->createStock($warehouse, 100);

        app(PopulateCatalogPrices::class)->execute(new PopulateCatalogPricesInput);

        $this->assertSame(200.0, $this->priceOf($stock, $city));
    }

    public function test_price_without_city_markup_when_no_rule(): void
    {
        $warehouse = Warehouse::factory()->create();
        WarehouseMarkupRule::create([
            'warehouse_id' => $warehouse->id, 'price_from' => 0, 'price_to' => 500, 'coefficient' => 1.5,
        ]);
        $city = $this->createCity();
        $stock = $this->createStock($warehouse, 100);

        app(PopulateCatalogPrices::class)->execute(new PopulateCatalogPricesInput);

        $this->assertSame(150.0, $this->priceOf($stock, $city));
    }

    private function createStock(Warehouse $warehouse, float $purchasePrice): Stock
    {
        $tire = TireProduct::factory()->create();

        return Stock::create([
            'stockable_type' => $tire->getMorphClass(),
            'stockable_id' => $tire->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 5,
            'purchase_price' => $purchasePrice,
        ]);
    }

    private function createWeeklySchedule(Warehouse $warehouse): void
    {
        foreach ([0, 1, 2, 3, 4, 5] as $day) {
            DeliverySchedule::create([
                'warehouse_id' => $warehouse->id,
                'day_of_week' => $day,
                'cutoff_time' => $day === 5 ? '12:00' : '14:00',
                'days_before' => $day === 5 ? 2 : 1,
                'days_after' => $day === 5 ? 3 : 2,
            ]);
        }
    }

    private function priceOf(Stock $stock, City $city): ?float
    {
        return CatalogPrice::where('stock_id', $stock->id)->where('city_id', $city->id)->value('price');
    }
}
