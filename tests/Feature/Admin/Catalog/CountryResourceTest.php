<?php

namespace Tests\Feature\Admin\Catalog;

use App\Filament\Resources\Countries\Pages\ListCountries;
use App\Models\Auth\Admin;
use App\Models\Auth\AdminRole;
use App\Models\Catalog\Country\Country;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** CountryResource панели: read-only список стран. */
class CountryResourceTest extends TestCase
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
