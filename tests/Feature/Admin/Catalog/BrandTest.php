<?php

namespace Tests\Feature\Admin\Catalog;

use App\Models\Auth\Admin;
use App\Models\Auth\AdminRole;
use App\Models\Catalog\Brand\Brand;
use App\Models\Catalog\Tire\TireProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** CRUD брендов. */
class BrandTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $role = AdminRole::create([
            'name' => 'Главный администратор',
            'code' => 'super-admin',
        ]);

        $this->admin = Admin::create([
            'name' => 'Admin',
            'email' => 'admin@test.ru',
            'password' => bcrypt('password'),
            'admin_role_id' => $role->id,
            'is_active' => true,
        ]);
    }

    public function test_index_returns_paginated_brands(): void
    {
        Brand::factory()->count(3)->create();

        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/catalog/brands')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'name', 'slug', 'type']],
                'meta' => ['total'],
            ]);
    }

    public function test_store_creates_brand(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/catalog/brands', [
                'name' => 'Test Brand',
                'slug' => 'test-brand',
                'type' => 'tire',
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Test Brand');

        $this->assertDatabaseHas('brands', ['slug' => 'test-brand']);
    }

    public function test_store_validates_required_fields(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/catalog/brands', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'slug', 'type']);
    }

    public function test_show_returns_brand(): void
    {
        $brand = Brand::factory()->create(['name' => 'Show Brand']);

        $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/admin/catalog/brands/{$brand->id}")
            ->assertOk()
            ->assertJsonPath('data.name', 'Show Brand');
    }

    public function test_update_modifies_brand(): void
    {
        $brand = Brand::factory()->create(['name' => 'Old Name']);

        $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/admin/catalog/brands/{$brand->id}", [
                'name' => 'New Name',
                'slug' => $brand->slug,
                'type' => 'wheel',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Name');
    }

    public function test_destroy_removes_brand_without_products(): void
    {
        $brand = Brand::factory()->create();

        $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/admin/catalog/brands/{$brand->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('brands', ['id' => $brand->id]);
    }

    public function test_destroy_blocked_when_products_exist(): void
    {
        $brand = Brand::factory()->create();
        TireProduct::factory()->create([
            'brand_id' => $brand->id,
        ]);

        $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/admin/catalog/brands/{$brand->id}")
            ->assertStatus(409)
            ->assertJsonPath('message', fn (string $m) => str_contains($m, 'Невозможно удалить'));
    }

    public function test_both_brand_covers_both_categories(): void
    {
        $brand = Brand::factory()->create(['type' => 'both']);

        $this->assertTrue($brand->isTireBrand());
        $this->assertTrue($brand->isWheelBrand());
    }

    public function test_tire_brand_covers_only_tires(): void
    {
        $brand = Brand::factory()->create(['type' => 'tire']);

        $this->assertTrue($brand->isTireBrand());
        $this->assertFalse($brand->isWheelBrand());
    }
}
