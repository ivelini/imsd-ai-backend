<?php

namespace Tests\Feature\Admin\Geo;

use App\Models\Auth\Admin;
use App\Models\Auth\AdminRole;
use App\Models\Delivery\City;
use App\Models\Delivery\CityPriceRule;
use App\Models\Delivery\Region;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** CRUD правил наценки городов. */
class CityPriceRuleTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    private City $city;

    protected function setUp(): void
    {
        parent::setUp();

        $role = AdminRole::create(['name' => 'Главный администратор', 'code' => 'super-admin']);
        $this->admin = Admin::create([
            'name' => 'Admin', 'email' => 'admin@test.ru',
            'password' => bcrypt('password'), 'admin_role_id' => $role->id, 'is_active' => true,
        ]);

        $region = Region::create(['code' => '74', 'name' => 'Челябинская область']);
        $this->city = City::create(['region_id' => $region->id, 'name' => 'Челябинск']);
    }

    public function test_index_returns_paginated_rules(): void
    {
        CityPriceRule::create(['city_id' => $this->city->id, 'price_from' => 0, 'price_to' => 5000, 'markup' => 300]);
        CityPriceRule::create(['city_id' => $this->city->id, 'price_from' => 5001, 'price_to' => 10000, 'markup' => 500]);

        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/geo/city-price-rules')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'city_id', 'price_from', 'price_to', 'markup']],
                'meta' => ['total'],
            ]);
    }

    public function test_index_filters_by_city(): void
    {
        $region2 = Region::create(['code' => '75', 'name' => 'Другой регион']);
        $city2 = City::create(['region_id' => $region2->id, 'name' => 'Магнитогорск']);

        CityPriceRule::create(['city_id' => $this->city->id, 'price_from' => 0, 'price_to' => 5000, 'markup' => 300]);
        CityPriceRule::create(['city_id' => $city2->id, 'price_from' => 0, 'price_to' => 5000, 'markup' => 200]);

        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/geo/city-price-rules?city_id='.$city2->id)
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_store_creates_rule(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/geo/city-price-rules', [
                'city_id' => $this->city->id,
                'price_from' => 0,
                'price_to' => 5000,
                'markup' => 300,
            ])
            ->assertCreated()
            ->assertJsonPath('data.markup', 300);

        $this->assertDatabaseHas('city_price_rules', ['city_id' => $this->city->id, 'markup' => 300]);
    }

    public function test_store_validates_required_fields(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/geo/city-price-rules', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['city_id', 'price_from', 'price_to', 'markup']);
    }

    public function test_show_returns_rule(): void
    {
        $rule = CityPriceRule::create(['city_id' => $this->city->id, 'price_from' => 0, 'price_to' => 5000, 'markup' => 300]);

        $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/admin/geo/city-price-rules/{$rule->id}")
            ->assertOk()
            ->assertJsonPath('data.markup', 300);
    }

    public function test_update_modifies_rule(): void
    {
        $rule = CityPriceRule::create(['city_id' => $this->city->id, 'price_from' => 0, 'price_to' => 5000, 'markup' => 300]);

        $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/admin/geo/city-price-rules/{$rule->id}", [
                'city_id' => $this->city->id,
                'price_from' => 0,
                'price_to' => 5000,
                'markup' => 500,
            ])
            ->assertOk()
            ->assertJsonPath('data.markup', 500);
    }

    public function test_destroy_removes_rule(): void
    {
        $rule = CityPriceRule::create(['city_id' => $this->city->id, 'price_from' => 0, 'price_to' => 5000, 'markup' => 300]);

        $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/admin/geo/city-price-rules/{$rule->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('city_price_rules', ['id' => $rule->id]);
    }
}
