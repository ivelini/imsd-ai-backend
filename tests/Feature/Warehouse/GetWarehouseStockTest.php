<?php

namespace Tests\Feature\Warehouse;

use App\Actions\Catalog\PopulateCatalogPrices;
use App\Actions\Warehouse\GetWarehouseStock;
use App\DTOs\Catalog\GetWarehouseStockInput;
use App\DTOs\Catalog\PopulateCatalogPricesInput;
use App\Http\Resources\Admin\Catalog\Warehouse\WarehouseStockRowResource;
use App\Models\Catalog\MarkupRule\WarehouseMarkupRule;
use App\Models\Catalog\Tire\TireProduct;
use App\Models\Catalog\Warehouse\Stock;
use App\Models\Catalog\Warehouse\Warehouse;
use App\Models\Delivery\CityDeliveryTime;
use App\Models\Delivery\CityPriceRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCity;
use Tests\TestCase;

/** Остатки товара на складах: цена с доставкой, без delivery_cost (HTTP-эндпоинт снесён, вернётся RelationManager'ом). */
class GetWarehouseStockTest extends TestCase
{
    use CreatesCity, RefreshDatabase;

    public function test_response_has_final_price_without_delivery_cost(): void
    {
        $warehouse = Warehouse::factory()->create();
        $this->createScheduleForToday($warehouse);
        WarehouseMarkupRule::create([
            'warehouse_id' => $warehouse->id, 'price_from' => 0, 'price_to' => 500, 'coefficient' => 1.5,
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
        CityDeliveryTime::create(['city_id' => $city->id, 'delivery_days' => 1]);
        CityPriceRule::create(['city_id' => $city->id, 'price_from' => 0, 'price_to' => 200, 'markup' => 50]);

        app(PopulateCatalogPrices::class)->execute(new PopulateCatalogPricesInput);

        $result = app(GetWarehouseStock::class)->execute(
            new GetWarehouseStockInput('tire', $tire->id, $city->id),
        );

        $row = WarehouseStockRowResource::collection($result->rows)->resolve()[0];

        $this->assertSame(200.0, (float) $row['final_price']);
        $this->assertArrayNotHasKey('delivery_cost', $row);
        $this->assertArrayHasKey('delivery_days', $row);
    }
}
