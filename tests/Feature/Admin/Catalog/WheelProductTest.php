<?php

namespace Tests\Feature\Admin\Catalog;

use App\Models\Auth\Admin;
use App\Models\Auth\AdminRole;
use App\Models\Catalog\Brand\Brand;
use App\Models\Catalog\Model\ProductModel;
use App\Models\Catalog\Promotion\Promotion;
use App\Models\Catalog\Warehouse\Stock;
use App\Models\Catalog\Warehouse\Warehouse;
use App\Models\Catalog\Wheel\WheelProduct;
use App\Models\Delivery\City;
use App\Models\Delivery\CityDeliveryTime;
use App\Models\Delivery\DeliverySchedule;
use App\Models\Delivery\Region;
use App\Models\Image;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/** HTTP-слой CRUD дисков. */
class WheelProductTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    private Brand $brand;

    private ProductModel $model;

    protected function setUp(): void
    {
        parent::setUp();

        $role = AdminRole::create(['name' => 'Главный администратор', 'code' => 'super-admin']);
        $this->admin = Admin::create([
            'name' => 'Admin', 'email' => 'admin@test.ru',
            'password' => bcrypt('password'), 'admin_role_id' => $role->id, 'is_active' => true,
        ]);

        $this->brand = Brand::factory()->create();
        $this->model = ProductModel::create([
            'brand_id' => $this->brand->id, 'name' => 'TestModel', 'slug' => 'test-model', 'type' => 'wheel',
        ]);
    }

    public function test_requires_auth(): void
    {
        $this->getJson('/api/admin/catalog/wheels')->assertUnauthorized();
    }

    public function test_crud(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/catalog/wheels', [
                'brand_id' => $this->brand->id,
                'model_id' => $this->model->id,
                'ean' => 'W-EAN',
            ]);
        $response->assertCreated();
        $id = $response->json('data.id');

        $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/admin/catalog/wheels/{$id}")
            ->assertOk()
            ->assertJsonPath('data.name', 'TestModel');

        $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/admin/catalog/wheels/{$id}", [
                'brand_id' => $this->brand->id,
                'model_id' => $this->model->id,
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'TestModel');

        $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/admin/catalog/wheels/{$id}")
            ->assertNoContent();
    }

    public function test_promotions_relation_returns_promotions_not_images(): void
    {
        $product = WheelProduct::factory()->create();

        $relation = $product->promotions();

        $this->assertInstanceOf(Promotion::class, $relation->getRelated());
        $this->assertNotInstanceOf(Image::class, $relation->getRelated());
    }

    public function test_show_enriches_delivery_with_city_id(): void
    {
        Carbon::setTestNow(now()->startOfDay()->addHours(10));
        $wheel = WheelProduct::factory()->create();
        $city = $this->createCityWithDeliveryTime();
        $warehouse = $this->createWarehouseWithScheduleForToday();

        Stock::create([
            'stockable_type' => $wheel->getMorphClass(),
            'stockable_id' => $wheel->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 5,
            'price' => 100,
            'purchase_price' => 50,
        ]);

        $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/admin/catalog/wheels/{$wheel->id}?city_id={$city->id}")
            ->assertOk()
            ->assertJsonPath('data.delivery.delivery_days', 3);
    }

    public function test_show_rejects_invalid_city_id(): void
    {
        $wheel = WheelProduct::factory()->create();

        $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/admin/catalog/wheels/{$wheel->id}?city_id=abc")
            ->assertUnprocessable();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function createCityWithDeliveryTime(): City
    {
        $region = Region::create(['code' => '74', 'name' => 'Челябинская область']);
        $city = City::create(['region_id' => $region->id, 'name' => 'Челябинск', 'sort' => 1]);

        CityDeliveryTime::create(['city_id' => $city->id, 'delivery_days' => 1, 'priority' => 1]);

        return $city;
    }

    private function createWarehouseWithScheduleForToday(): Warehouse
    {
        $warehouse = Warehouse::factory()->create();

        DeliverySchedule::create([
            'warehouse_id' => $warehouse->id,
            'day_of_week' => now()->dayOfWeekIso - 1,
            'cutoff_time' => '18:00',
            'days_before' => 2,
            'days_after' => 5,
        ]);

        return $warehouse;
    }
}
