<?php

namespace Tests\Feature\Admin\Catalog;

use App\Models\Auth\Admin;
use App\Models\Auth\AdminRole;
use App\Models\Catalog\Brand\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** HTTP-слой акций. */
class PromotionTest extends TestCase
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
        $this->getJson('/api/admin/catalog/promotions')->assertUnauthorized();
    }

    public function test_crud(): void
    {
        // create
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/catalog/promotions', [
                'name' => 'Test Sale',
                'type' => 'percent',
                'value' => 10,
                'starts_at' => '2026-07-01T00:00:00Z',
                'ends_at' => '2026-08-01T00:00:00Z',
            ]);
        $response->assertCreated();
        $id = $response->json('data.id');

        // read
        $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/admin/catalog/promotions/{$id}")
            ->assertOk()
            ->assertJsonPath('data.name', 'Test Sale');

        // update
        $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/admin/catalog/promotions/{$id}", [
                'name' => 'Updated Sale',
                'type' => 'percent',
                'value' => 15,
                'starts_at' => '2026-07-01T00:00:00Z',
                'ends_at' => '2026-08-01T00:00:00Z',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated Sale');

        // delete
        $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/admin/catalog/promotions/{$id}")
            ->assertNoContent();
    }

    public function test_store_with_brand_binding(): void
    {
        $brand = Brand::factory()->create();

        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/catalog/promotions', [
                'name' => 'Brand Sale',
                'type' => 'percent',
                'value' => 20,
                'starts_at' => '2026-07-01T00:00:00Z',
                'ends_at' => '2026-08-01T00:00:00Z',
                'promotable_type' => 'brand',
                'promotable_id' => $brand->id,
            ])
            ->assertCreated()
            ->assertJsonPath('data.promotable_type', 'brand');
    }
}
