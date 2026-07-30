<?php

namespace Tests\Feature\Admin\Catalog;

use App\Models\Auth\Admin;
use App\Models\Auth\AdminRole;
use App\Models\Catalog\Supplier\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** CRUD поставщиков. */
class SupplierTest extends TestCase
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
        Supplier::factory()->count(3)->create();

        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/catalog/suppliers')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'name']], 'meta']);
    }

    public function test_store(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/catalog/suppliers', ['name' => 'Test Supplier', 'code' => 'TS-001'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Test Supplier');
    }

    public function test_store_validates(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/catalog/suppliers', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    public function test_show(): void
    {
        $supplier = Supplier::factory()->create(['name' => 'Test Co']);

        $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/admin/catalog/suppliers/{$supplier->id}")
            ->assertOk()
            ->assertJsonPath('data.name', 'Test Co');
    }

    public function test_update(): void
    {
        $supplier = Supplier::factory()->create();

        $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/admin/catalog/suppliers/{$supplier->id}", [
                'name' => 'Updated Co',
                'code' => $supplier->code,
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated Co');
    }

    public function test_destroy(): void
    {
        $supplier = Supplier::factory()->create();

        $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/admin/catalog/suppliers/{$supplier->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('suppliers', ['id' => $supplier->id]);
    }
}
