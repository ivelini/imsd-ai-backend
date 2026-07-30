<?php

namespace Tests\Feature\Admin\Catalog;

use App\Models\Auth\Admin;
use App\Models\Auth\AdminRole;
use App\Models\Catalog\Warehouse\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** CRUD складов. */
class WarehouseTest extends TestCase
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

    public function test_index(): void
    {
        Warehouse::factory()->count(3)->create();

        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/catalog/warehouses')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'name']], 'meta']);
    }

    public function test_store(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/catalog/warehouses', ['name' => 'Main Warehouse'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Main Warehouse');
    }

    public function test_show(): void
    {
        $warehouse = Warehouse::factory()->create(['name' => 'WH East']);

        $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/admin/catalog/warehouses/{$warehouse->id}")
            ->assertOk()
            ->assertJsonPath('data.name', 'WH East');
    }

    public function test_update(): void
    {
        $warehouse = Warehouse::factory()->create();

        $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/admin/catalog/warehouses/{$warehouse->id}", ['name' => 'WH West'])
            ->assertOk()
            ->assertJsonPath('data.name', 'WH West');
    }

    public function test_destroy(): void
    {
        $warehouse = Warehouse::factory()->create();

        $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/admin/catalog/warehouses/{$warehouse->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('warehouses', ['id' => $warehouse->id]);
    }
}
