<?php

namespace Tests\Feature\Admin\Geo;

use App\Filament\Resources\Cities\Pages\ListCities;
use App\Models\Auth\Admin;
use App\Models\Auth\AdminRole;
use App\Models\Delivery\City;
use App\Models\Delivery\Region;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** CityResource панели: read-only список городов. */
class CityResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $role = AdminRole::create(['name' => 'Главный администратор', 'code' => 'super-admin']);
        $admin = Admin::create([
            'name' => 'Admin',
            'email' => 'admin@test.ru',
            'password' => bcrypt('password'),
            'admin_role_id' => $role->id,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin');
    }

    public function test_list_shows_cities_with_region(): void
    {
        $region = Region::create(['code' => '74', 'name' => 'Челябинская область']);
        $cities = collect([
            City::create(['region_id' => $region->id, 'name' => 'Челябинск', 'sort' => 1]),
            City::create(['region_id' => $region->id, 'name' => 'Магнитогорск', 'sort' => 2]),
        ]);

        Livewire::test(ListCities::class)
            ->assertCanSeeTableRecords($cities);
    }
}
