<?php

namespace Tests\Feature\Admin\Catalog;

use App\Models\Auth\Admin;
use App\Models\Auth\AdminRole;
use App\Models\Catalog\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** HTTP-слой CRUD дисков. */
class WheelProductTest extends TestCase
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
        $this->getJson('/api/admin/catalog/wheels')->assertUnauthorized();
    }

    public function test_crud(): void
    {
        $brand = Brand::factory()->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/catalog/wheels', [
                'brand_id' => $brand->id, 'name' => 'Test Wheel', 'ean' => 'W-EAN',
            ]);
        $response->assertCreated();
        $id = $response->json('data.id');

        $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/admin/catalog/wheels/{$id}")
            ->assertOk()
            ->assertJsonPath('data.name', 'Test Wheel');

        $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/admin/catalog/wheels/{$id}", [
                'brand_id' => $brand->id, 'name' => 'Updated',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated');

        $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/admin/catalog/wheels/{$id}")
            ->assertNoContent();
    }
}
