<?php

namespace Tests\Feature\Admin\Catalog;

use App\Models\Auth\Admin;
use App\Models\Catalog\Tire\TireProduct;
use App\Models\Catalog\Wheel\WheelProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesAdmin;
use Tests\TestCase;

/** Снос admin API товаров волны 2b: маршруты шин, дисков и агрегированного списка удалены. */
class TireWheelApiRemovalTest extends TestCase
{
    use CreatesAdmin, RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->createAdmin();
    }

    public function test_tire_crud_routes_removed(): void
    {
        $tire = TireProduct::factory()->create();

        $this->authGetJson('/api/admin/catalog/tires')->assertNotFound();
        $this->authPostJson('/api/admin/catalog/tires')->assertNotFound();
        $this->authGetJson("/api/admin/catalog/tires/{$tire->id}")->assertNotFound();
        $this->authPutJson("/api/admin/catalog/tires/{$tire->id}")->assertNotFound();
        $this->authDeleteJson("/api/admin/catalog/tires/{$tire->id}")->assertNotFound();
    }

    public function test_wheel_crud_routes_removed(): void
    {
        $wheel = WheelProduct::factory()->create();

        $this->authGetJson('/api/admin/catalog/wheels')->assertNotFound();
        $this->authPostJson('/api/admin/catalog/wheels')->assertNotFound();
        $this->authGetJson("/api/admin/catalog/wheels/{$wheel->id}")->assertNotFound();
        $this->authPutJson("/api/admin/catalog/wheels/{$wheel->id}")->assertNotFound();
        $this->authDeleteJson("/api/admin/catalog/wheels/{$wheel->id}")->assertNotFound();
    }

    public function test_dimensions_routes_removed(): void
    {
        $this->authGetJson('/api/admin/catalog/tires/dimensions')->assertNotFound();
        $this->authGetJson('/api/admin/catalog/wheels/dimensions')->assertNotFound();
    }

    public function test_warehouse_stock_routes_removed(): void
    {
        $tire = TireProduct::factory()->create();
        $wheel = WheelProduct::factory()->create();

        $this->authGetJson("/api/admin/catalog/tires/{$tire->id}/warehouse-stock")->assertNotFound();
        $this->authGetJson("/api/admin/catalog/wheels/{$wheel->id}/warehouse-stock")->assertNotFound();
    }

    public function test_products_route_removed(): void
    {
        $this->authGetJson('/api/admin/catalog/products')->assertNotFound();
    }

    public function test_unmigrated_route_still_works(): void
    {
        $this->authGetJson('/api/admin/catalog/references')->assertOk();
    }

    private function authGetJson(string $uri)
    {
        return $this->actingAs($this->admin, 'sanctum')->getJson($uri);
    }

    private function authPostJson(string $uri)
    {
        return $this->actingAs($this->admin, 'sanctum')->postJson($uri);
    }

    private function authPutJson(string $uri)
    {
        return $this->actingAs($this->admin, 'sanctum')->putJson($uri);
    }

    private function authDeleteJson(string $uri)
    {
        return $this->actingAs($this->admin, 'sanctum')->deleteJson($uri);
    }
}
