<?php

namespace Tests\Feature\Admin\Catalog;

use App\Models\Auth\Admin;
use App\Models\Auth\AdminRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Снос admin API справочников волны 1: маршруты удалены после переноса. */
class DirectoryApiRemovalTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $role = AdminRole::create(['name' => 'Главный администратор', 'code' => 'super-admin']);
        $this->admin = Admin::create([
            'name' => 'Admin',
            'email' => 'admin@test.ru',
            'password' => bcrypt('password'),
            'admin_role_id' => $role->id,
            'is_active' => true,
        ]);
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

    private function authGetJson(string $uri)
    {
        return $this->actingAs($this->admin, 'sanctum')->getJson($uri);
    }
}
