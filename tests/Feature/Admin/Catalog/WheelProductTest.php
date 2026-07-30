<?php

namespace Tests\Feature\Admin\Catalog;

use App\Models\Auth\Admin;
use App\Models\Auth\AdminRole;
use App\Models\Catalog\Brand\Brand;
use App\Models\Catalog\Model\ProductModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** HTTP-слой CRUD дисков. */
class WheelProductTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    private Brand $brand;

    private ProductModel $model;

    protected function setUp(): void
    {
        parent::setUp();

        $role = AdminRole::create(['name' => 'Главный администратор', 'code' => 'super-admin']);
        $this->admin = Admin::create([
            'name' => 'Admin', 'email' => 'admin@test.ru',
            'password' => bcrypt('password'), 'admin_role_id' => $role->id, 'is_active' => true,
        ]);

        $this->brand = Brand::factory()->create();
        $this->model = ProductModel::create([
            'brand_id' => $this->brand->id, 'name' => 'TestModel', 'slug' => 'test-model', 'type' => 'wheel',
        ]);
    }

    public function test_requires_auth(): void
    {
        $this->getJson('/api/admin/catalog/wheels')->assertUnauthorized();
    }

    public function test_crud(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/catalog/wheels', [
                'brand_id' => $this->brand->id,
                'model_id' => $this->model->id,
                'ean' => 'W-EAN',
            ]);
        $response->assertCreated();
        $id = $response->json('data.id');

        $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/admin/catalog/wheels/{$id}")
            ->assertOk()
            ->assertJsonPath('data.name', 'TestModel');

        $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/admin/catalog/wheels/{$id}", [
                'brand_id' => $this->brand->id,
                'model_id' => $this->model->id,
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'TestModel');

        $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/admin/catalog/wheels/{$id}")
            ->assertNoContent();
    }
}
