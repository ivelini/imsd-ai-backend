<?php

namespace Tests\Feature\Admin\Catalog;

use App\Models\Auth\Admin;
use App\Models\Auth\AdminRole;
use App\Models\Catalog\Wheel\WheelProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Тесты фильтрации списка дисков по размерным полям. */
class WheelProductFilterTest extends TestCase
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

        WheelProduct::factory()->createMany([
            ['width' => 6.5, 'diameter' => 16, 'pcd' => '5x112', 'et' => 35, 'type' => 'alloy', 'color' => 'black', 'is_bestseller' => true, 'is_new' => false],
            ['width' => 7.0, 'diameter' => 17, 'pcd' => '5x114.3', 'et' => 40, 'type' => 'steel', 'color' => 'silver', 'is_bestseller' => false, 'is_new' => true],
            ['width' => 7.5, 'diameter' => 18, 'pcd' => '5x120', 'et' => 45, 'type' => 'forged', 'color' => 'black', 'is_bestseller' => true, 'is_new' => true],
        ]);
    }

    public function test_index_filters_by_width(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/catalog/wheels?width[]=6.5&width[]=7.0');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    public function test_index_filters_by_pcd(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/catalog/wheels?pcd[]=5x112');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_index_filters_by_et(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/catalog/wheels?et[]=35');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_index_filters_by_is_bestseller(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/catalog/wheels?is_bestseller=1');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    public function test_index_combines_type_and_dimension_filters(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/catalog/wheels?type=alloy&width[]=6.5');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
    }

    public function test_dimensions_returns_all_filter_values(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/catalog/wheels/dimensions');

        $response->assertOk();
        $data = $response->json('data');

        $this->assertIsArray($data['widths']);
        $this->assertIsArray($data['diameters']);
        $this->assertIsArray($data['pcds']);
        $this->assertIsArray($data['ets']);
        $this->assertIsArray($data['types']);
        $this->assertIsArray($data['colors']);
        $this->assertIsArray($data['brands']);
    }

    public function test_dimensions_respects_type_filter(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/catalog/wheels/dimensions?type=alloy');

        $response->assertOk();
        $data = $response->json('data');

        $this->assertEquals([6.5], $data['widths']);
    }

    public function test_dimensions_respects_pcd_filter(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/catalog/wheels/dimensions?pcd[]=5x120');

        $response->assertOk();
        $data = $response->json('data');

        $this->assertEquals([18], $data['diameters']);
    }

    public function test_dimensions_requires_auth(): void
    {
        $response = $this->getJson('/api/admin/catalog/wheels/dimensions');

        $response->assertUnauthorized();
    }
}
