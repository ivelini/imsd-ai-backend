<?php

namespace Tests\Feature\Admin\Geo;

use App\Models\Auth\Admin;
use App\Models\Auth\AdminRole;
use App\Models\Delivery\City;
use App\Models\Delivery\Region;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Список городов для дропдауна. */
class CityTest extends TestCase
{
    use RefreshDatabase;

    private const string BASE_URL = '/api/admin/geo/cities';

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

    public function test_returns_all_cities(): void
    {
        $region = Region::create(['code' => '74', 'name' => 'Челябинская область']);
        City::create(['region_id' => $region->id, 'name' => 'Челябинск', 'sort' => 1]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson(self::BASE_URL)
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'name', 'region']]]);

        $this->assertCount(1, $response->json('data'));
    }

    public function test_filters_by_search(): void
    {
        $region = Region::create(['code' => '74', 'name' => 'Челябинская область']);
        City::create(['region_id' => $region->id, 'name' => 'Челябинск', 'sort' => 1]);
        City::create(['region_id' => $region->id, 'name' => 'Магнитогорск', 'sort' => 2]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson(self::BASE_URL.'?search=Челябинск')
            ->assertOk();

        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Челябинск', $response->json('data.0.name'));
    }

    public function test_filters_by_region_code(): void
    {
        $region74 = Region::create(['code' => '74', 'name' => 'Челябинская область']);
        $region77 = Region::create(['code' => '77', 'name' => 'Московская область']);
        City::create(['region_id' => $region74->id, 'name' => 'Челябинск', 'sort' => 1]);
        City::create(['region_id' => $region74->id, 'name' => 'Магнитогорск', 'sort' => 2]);
        City::create(['region_id' => $region77->id, 'name' => 'Москва', 'sort' => 1]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson(self::BASE_URL.'?region_code=74')
            ->assertOk();

        $this->assertCount(2, $response->json('data'));
    }

    public function test_returns_region_name(): void
    {
        $region = Region::create(['code' => '74', 'name' => 'Челябинская область']);
        City::create(['region_id' => $region->id, 'name' => 'Челябинск', 'sort' => 1]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson(self::BASE_URL)
            ->assertOk();

        $this->assertEquals('Челябинская область', $response->json('data.0.region'));
    }

    public function test_orders_by_sort_then_name(): void
    {
        $region = Region::create(['code' => '74', 'name' => 'Челябинская область']);
        City::create(['region_id' => $region->id, 'name' => 'Челябинск', 'sort' => 1]);
        City::create(['region_id' => $region->id, 'name' => 'Магнитогорск', 'sort' => 2]);
        City::create(['region_id' => $region->id, 'name' => 'Аша', 'sort' => 1]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson(self::BASE_URL)
            ->assertOk();

        $names = collect($response->json('data'))->pluck('name')->toArray();
        $this->assertEquals(['Аша', 'Челябинск', 'Магнитогорск'], $names);
    }

    public function test_returns_empty_when_no_match(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->getJson(self::BASE_URL.'?search=Несуществующий')
            ->assertOk()
            ->assertJson(['data' => []]);
    }
}
