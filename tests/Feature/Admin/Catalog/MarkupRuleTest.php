<?php

namespace Tests\Feature\Admin\Catalog;

use App\Models\Auth\Admin;
use App\Models\Auth\AdminRole;
use App\Models\Catalog\MarkupRule\WarehouseMarkupRule;
use App\Models\Catalog\Warehouse\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** HTTP-слой правил наценки. */
class MarkupRuleTest extends TestCase
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
        $this->getJson('/api/admin/catalog/markup-rules')->assertUnauthorized();
    }

    public function test_crud(): void
    {
        $warehouse = Warehouse::factory()->create();

        // create
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/catalog/markup-rules', [
                'warehouse_id' => $warehouse->id,
                'price_from' => 0,
                'price_to' => 5000,
                'coefficient' => 1.2,
            ]);
        $response->assertCreated();
        $id = $response->json('data.id');

        // read
        $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/admin/catalog/markup-rules/{$id}")
            ->assertOk()
            ->assertJsonPath('data.coefficient', 1.2);

        // update
        $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/admin/catalog/markup-rules/{$id}", [
                'warehouse_id' => $warehouse->id,
                'price_from' => 0,
                'price_to' => 10000,
                'coefficient' => 1.5,
            ])
            ->assertOk()
            ->assertJsonPath('data.coefficient', 1.5);

        // delete
        $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/admin/catalog/markup-rules/{$id}")
            ->assertNoContent();
    }

    public function test_filters_by_warehouse(): void
    {
        $wh1 = Warehouse::factory()->create();
        $wh2 = Warehouse::factory()->create();

        WarehouseMarkupRule::factory()->create(['warehouse_id' => $wh1->id]);
        WarehouseMarkupRule::factory()->create(['warehouse_id' => $wh2->id]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/catalog/markup-rules')
            ->assertOk();

        $this->assertCount(2, $response->json('data'));
    }
}
