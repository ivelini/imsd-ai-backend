<?php

namespace Tests\Feature\Warehouse;

use App\Actions\Catalog\PopulateCatalogPrices;
use App\DTOs\Catalog\PopulateCatalogPricesInput;
use App\Models\Auth\Admin;
use App\Models\Auth\AdminRole;
use App\Models\Catalog\MarkupRule\WarehouseMarkupRule;
use App\Models\Catalog\Tire\TireProduct;
use App\Models\Catalog\Warehouse\Stock;
use App\Models\Catalog\Warehouse\Warehouse;
use App\Models\Delivery\CityDeliveryTime;
use App\Models\Delivery\CityPriceRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCity;
use Tests\TestCase;

/** Эндпоинт остатков товара на складах: цена с доставкой, без delivery_cost. */
class GetWarehouseStockTest extends TestCase
{
    use CreatesCity, RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $role = AdminRole::create(['name' => 'Главный администратор', 'code' => 'super-admin']);
        $this->admin = Admin::create([
            'name' => 'Admin', 'email' => 'admin@test.ru',
            'password' => bcrypt('password'), 'admin_role_id' => $role->id, 'is_active' => true,
        ]);
    }

    public function test_response_has_final_price_without_delivery_cost(): void
    {
        $warehouse = Warehouse::factory()->create();
        $this->createScheduleForToday($warehouse);
        WarehouseMarkupRule::create([
            'warehouse_id' => $warehouse->id, 'price_from' => 0, 'price_to' => 500, 'coefficient' => 1.5,
        ]);

        $tire = TireProduct::factory()->create();
        $stock = Stock::create([
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

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/admin/catalog/tires/{$tire->id}/warehouse-stock?city_id={$city->id}")
            ->assertOk();

        $row = $response->json('data.0');
        $this->assertSame(200.0, (float) $row['final_price']);
        $this->assertArrayNotHasKey('delivery_cost', $row);
        $this->assertArrayHasKey('delivery_days', $row);
    }
}
