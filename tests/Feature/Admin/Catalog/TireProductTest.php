<?php

namespace Tests\Feature\Admin\Catalog;

use App\Models\Auth\Admin;
use App\Models\Auth\AdminRole;
use App\Models\Catalog\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** HTTP-слой CRUD шин. */
class TireProductTest extends TestCase
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
        $this->getJson('/api/admin/catalog/tires')->assertUnauthorized();
        $this->postJson('/api/admin/catalog/tires')->assertUnauthorized();
    }

    public function test_crud(): void
    {
        $brand = Brand::factory()->create();

        // create
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/catalog/tires', [
                'brand_id' => $brand->id, 'name' => 'Test', 'ean' => 'T-EAN', 'season' => 'summer',
            ]);
        $response->assertCreated();
        $id = $response->json('data.id');

        // read
        $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/admin/catalog/tires/{$id}")
            ->assertOk()
            ->assertJsonPath('data.name', 'Test');

        // update
        $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/admin/catalog/tires/{$id}", [
                'brand_id' => $brand->id, 'name' => 'Updated', 'season' => 'winter',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated');

        // delete
        $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/admin/catalog/tires/{$id}")
            ->assertNoContent();
    }
}
