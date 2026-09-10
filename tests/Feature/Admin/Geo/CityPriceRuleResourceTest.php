<?php

namespace Tests\Feature\Admin\Geo;

use App\Filament\Clusters\Catalog\Resources\CityPriceRules\Pages\CreateCityPriceRule;
use App\Filament\Clusters\Catalog\Resources\CityPriceRules\Pages\EditCityPriceRule;
use App\Filament\Clusters\Catalog\Resources\CityPriceRules\Pages\ListCityPriceRules;
use App\Models\Auth\Admin;
use App\Models\Delivery\City;
use App\Models\Delivery\CityPriceRule;
use App\Models\Delivery\Region;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesAdmin;
use Tests\TestCase;

/** CityPriceRuleResource панели: CRUD наценок по городам. */
class CityPriceRuleResourceTest extends TestCase
{
    use CreatesAdmin, RefreshDatabase;

    private Admin $admin;

    private City $city;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->createAdmin();

        $region = Region::create(['code' => '74', 'name' => 'Челябинская область']);
        $this->city = City::create(['region_id' => $region->id, 'name' => 'Челябинск']);

        $this->actingAs($this->admin, 'admin');
    }

    public function test_create_rule_validates_and_saves(): void
    {
        Livewire::test(CreateCityPriceRule::class)
            ->fillForm([
                'city_id' => $this->city->id,
                'price_from' => 0,
                'price_to' => 20000,
                'markup' => 500,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('city_price_rules', [
            'city_id' => $this->city->id,
            'price_to' => 20000,
        ]);
    }

    public function test_update_rule_changes_fields(): void
    {
        $rule = CityPriceRule::create([
            'city_id' => $this->city->id,
            'price_from' => 0,
            'price_to' => 20000,
            'markup' => 500,
        ]);

        Livewire::test(EditCityPriceRule::class, ['record' => $rule->id])
            ->fillForm([
                'city_id' => $this->city->id,
                'price_from' => 0,
                'price_to' => 50000,
                'markup' => 1000,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('city_price_rules', ['id' => $rule->id, 'price_to' => 50000, 'markup' => 1000]);
    }

    public function test_delete_rule_removes_record(): void
    {
        $rule = CityPriceRule::create([
            'city_id' => $this->city->id,
            'price_from' => 0,
            'price_to' => 20000,
            'markup' => 500,
        ]);

        Livewire::test(ListCityPriceRules::class)
            ->callTableAction('delete', $rule);

        $this->assertDatabaseMissing('city_price_rules', ['id' => $rule->id]);
    }
}
