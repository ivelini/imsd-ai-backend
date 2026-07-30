<?php

namespace Tests\Feature\Admin\Catalog;

use App\Models\Auth\Admin;
use App\Models\Auth\AdminRole;
use App\Models\Catalog\Tire\TireProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Тесты фильтрации списка шин по размерным полям. */
class TireProductFilterTest extends TestCase
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

        TireProduct::factory()->createMany([
            ['width' => 205, 'profile' => 55, 'diameter' => 'R16', 'season' => 'winter', 'is_studded' => true, 'is_bestseller' => true, 'is_new' => false],
            ['width' => 225, 'profile' => 55, 'diameter' => 'R17', 'season' => 'summer', 'is_studded' => false, 'is_bestseller' => false, 'is_new' => true],
            ['width' => 245, 'profile' => 45, 'diameter' => 'R18', 'season' => 'all-season', 'is_studded' => false, 'is_bestseller' => true, 'is_new' => true],
        ]);
    }

    public function test_index_filters_by_width(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/catalog/tires?width[]=205&width[]=225');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    public function test_index_filters_by_profile(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/catalog/tires?profile[]=45');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_index_filters_by_diameter(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/catalog/tires?diameter[]=R16');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_index_filters_by_is_bestseller(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/catalog/tires?is_bestseller=1');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    public function test_index_filters_by_is_new(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/catalog/tires?is_new=1');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    public function test_index_combines_dimension_filters(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/catalog/tires?width[]=205&season=winter');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_index_with_no_matching_dimensions_returns_empty(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/catalog/tires?width[]=495');

        $response->assertOk();
        $this->assertCount(0, $response->json('data'));
    }

    public function test_dimensions_returns_all_filter_values(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/catalog/tires/dimensions');

        $response->assertOk();
        $data = $response->json('data');

        $this->assertIsArray($data['widths']);
        $this->assertIsArray($data['profiles']);
        $this->assertIsArray($data['diameters']);
        $this->assertIsArray($data['seasons']);
        $this->assertIsArray($data['brands']);
    }

    public function test_dimensions_respects_season_filter(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/catalog/tires/dimensions?season=winter');

        $response->assertOk();
        $data = $response->json('data');

        $this->assertEquals([205], $data['widths']);
    }

    public function test_dimensions_respects_width_filter(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/catalog/tires/dimensions?width[]=245');

        $response->assertOk();
        $data = $response->json('data');

        $this->assertEquals([45], $data['profiles']);
        $this->assertEquals(['R18'], $data['diameters']);
    }

    public function test_dimensions_requires_auth(): void
    {
        $response = $this->getJson('/api/admin/catalog/tires/dimensions');

        $response->assertUnauthorized();
    }
}
