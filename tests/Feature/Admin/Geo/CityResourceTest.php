<?php

namespace Tests\Feature\Admin\Geo;

use App\Filament\Resources\Cities\Pages\ListCities;
use App\Models\Delivery\City;
use App\Models\Delivery\Region;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesAdmin;
use Tests\TestCase;

/** CityResource панели: read-only список городов. */
class CityResourceTest extends TestCase
{
    use CreatesAdmin, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = $this->createAdmin();

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
