<?php

namespace Tests\Feature\Admin\Catalog;

use App\Filament\Resources\Countries\Pages\ListCountries;
use App\Models\Catalog\Country\Country;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesAdmin;
use Tests\TestCase;

/** CountryResource панели: read-only список стран. */
class CountryResourceTest extends TestCase
{
    use CreatesAdmin, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = $this->createAdmin();

        $this->actingAs($admin, 'admin');
    }

    public function test_list_shows_countries(): void
    {
        $countries = collect([
            Country::create(['name' => 'Китай', 'slug' => 'china']),
            Country::create(['name' => 'Россия', 'slug' => 'russia']),
        ]);

        Livewire::test(ListCountries::class)
            ->assertCanSeeTableRecords($countries);
    }
}
