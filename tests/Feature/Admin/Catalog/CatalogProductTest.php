<?php

namespace Tests\Feature\Admin\Catalog;

use App\Models\Auth\Admin;
use App\Models\Auth\AdminRole;
use App\Models\Catalog\TireProduct;
use App\Models\Catalog\WheelProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** HTTP-слой списка каталога. */
class CatalogProductTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_requires_auth(): void
    {
        $this->getJson('/api/admin/catalog/products')->assertUnauthorized();
    }

    public function test_returns_both_types(): void
    {
        TireProduct::factory()->count(2)->create();
        WheelProduct::factory()->count(3)->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/catalog/products')
            ->assertOk();

        $this->assertCount(5, $response->json('data'));
    }

    public function test_filters_by_type(): void
    {
        TireProduct::factory()->count(3)->create();
        WheelProduct::factory()->count(2)->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/catalog/products?type=tire')
            ->assertOk();

        $this->assertCount(3, $response->json('data'));
    }
}
