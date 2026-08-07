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
use App\Models\Delivery\Region;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Массовый пересчёт catalog_prices с учётом наценок складов. */
class PopulateCatalogPricesTest extends TestCase
{
    use RefreshDatabase;

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

    private function createCity(): City
    {
        $region = Region::create(['code' => '74', 'name' => 'Челябинская область']);

        return City::create(['region_id' => $region->id, 'name' => 'Челябинск', 'sort' => 1]);
    }
}
