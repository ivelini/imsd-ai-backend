<?php

namespace Tests\Feature\Admin\Catalog;

use App\Models\Auth\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesAdmin;
use Tests\TestCase;

/** Снос admin API справочников волны 1: маршруты удалены после переноса. */
class DirectoryApiRemovalTest extends TestCase
{
    use CreatesAdmin, RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->createAdmin();
    }

    public function test_warehouses_routes_removed(): void
    {
        $this->authGetJson('/api/admin/catalog/warehouses')->assertNotFound();
    }

    public function test_markup_rules_routes_removed(): void
    {
        $this->authGetJson('/api/admin/catalog/markup-rules')->assertNotFound();
    }

    public function test_delivery_schedules_routes_removed(): void
    {
        $this->authGetJson('/api/admin/catalog/delivery-schedules')->assertNotFound();
    }

    public function test_city_price_rules_routes_removed(): void
    {
        $this->authGetJson('/api/admin/catalog/geo/city-price-rules')->assertNotFound();
    }

    public function test_delivery_points_routes_removed(): void
    {
        $this->authGetJson('/api/admin/catalog/geo/delivery-points')->assertNotFound();
    }

    public function test_cities_routes_removed(): void
    {
        $this->authGetJson('/api/admin/catalog/geo/cities')->assertNotFound();
    }

    public function test_countries_routes_removed(): void
    {
        $this->authGetJson('/api/admin/catalog/countries')->assertNotFound();
    }

    public function test_models_routes_removed(): void
    {
        $this->authGetJson('/api/admin/catalog/models')->assertNotFound();
    }

    private function authGetJson(string $uri)
    {
        return $this->actingAs($this->admin, 'sanctum')->getJson($uri);
    }
}
