<?php

namespace Tests\Feature\Admin\Geo;

use App\Models\Auth\Admin;
use App\Models\Auth\AdminRole;
use App\Models\Delivery\City;
use App\Models\Delivery\DeliveryPoint;
use App\Models\Delivery\Region;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** CRUD точек выдачи. */
class DeliveryPointTest extends TestCase
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

    public function test_index_returns_paginated_points(): void
    {
        DeliveryPoint::create(['city_id' => $this->city->id, 'address' => 'ул. Ленина, 1']);
        DeliveryPoint::create(['city_id' => $this->city->id, 'address' => 'ул. Воровского, 2']);

        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/catalog/geo/delivery-points')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'city_id', 'address']],
                'meta' => ['total'],
            ]);
    }

    public function test_index_filters_by_city(): void
    {
        $region2 = Region::create(['code' => '75', 'name' => 'Другой регион']);
        $city2 = City::create(['region_id' => $region2->id, 'name' => 'Магнитогорск']);

        DeliveryPoint::create(['city_id' => $this->city->id, 'address' => 'ул. Ленина, 1']);
        DeliveryPoint::create(['city_id' => $city2->id, 'address' => 'ул. Пушкина, 5']);

        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/catalog/geo/delivery-points?city_id='.$city2->id)
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_index_searches_by_address(): void
    {
        DeliveryPoint::create(['city_id' => $this->city->id, 'address' => 'ул. Ленина, 1']);
        DeliveryPoint::create(['city_id' => $this->city->id, 'address' => 'ул. Пушкина, 5']);

        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/catalog/geo/delivery-points?search=Ленина')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_store_creates_point(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/catalog/geo/delivery-points', [
                'city_id' => $this->city->id,
                'address' => 'ул. Тестовая, 10',
                'phone' => '+7 (351) 000-00-00',
                'pickup_from_truck' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.address', 'ул. Тестовая, 10');

        $this->assertDatabaseHas('delivery_points', ['address' => 'ул. Тестовая, 10']);
    }

    public function test_store_validates_required_fields(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/catalog/geo/delivery-points', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['city_id', 'address']);
    }

    public function test_show_returns_point(): void
    {
        $point = DeliveryPoint::create(['city_id' => $this->city->id, 'address' => 'ул. Ленина, 1']);

        $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/admin/catalog/geo/delivery-points/{$point->id}")
            ->assertOk()
            ->assertJsonPath('data.address', 'ул. Ленина, 1');
    }

    public function test_update_modifies_point(): void
    {
        $point = DeliveryPoint::create(['city_id' => $this->city->id, 'address' => 'Старый адрес']);

        $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/admin/catalog/geo/delivery-points/{$point->id}", [
                'city_id' => $this->city->id,
                'address' => 'Новый адрес',
                'phone' => '+7 (351) 111-11-11',
            ])
            ->assertOk()
            ->assertJsonPath('data.address', 'Новый адрес');
    }

    public function test_destroy_removes_point(): void
    {
        $point = DeliveryPoint::create(['city_id' => $this->city->id, 'address' => 'ул. Ленина, 1']);

        $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/admin/catalog/geo/delivery-points/{$point->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('delivery_points', ['id' => $point->id]);
    }
}
