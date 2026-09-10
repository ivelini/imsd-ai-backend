<?php

namespace Tests\Feature\Admin\Geo;

use App\Filament\Resources\DeliveryPoints\Pages\CreateDeliveryPoint;
use App\Filament\Resources\DeliveryPoints\Pages\EditDeliveryPoint;
use App\Filament\Resources\DeliveryPoints\Pages\ListDeliveryPoints;
use App\Models\Auth\Admin;
use App\Models\Delivery\City;
use App\Models\Delivery\DeliveryPoint;
use App\Models\Delivery\Region;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesAdmin;
use Tests\TestCase;

/** DeliveryPointResource панели: CRUD точек выдачи. */
class DeliveryPointResourceTest extends TestCase
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

    public function test_create_point_validates_and_saves(): void
    {
        Livewire::test(CreateDeliveryPoint::class)
            ->fillForm([
                'city_id' => $this->city->id,
                'address' => 'ул. Ленина, 1',
                'phone' => '+7 900 000-00-00',
                'pickup_from_truck' => false,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('delivery_points', [
            'city_id' => $this->city->id,
            'address' => 'ул. Ленина, 1',
        ]);
    }

    public function test_update_point_changes_fields(): void
    {
        $point = DeliveryPoint::create(['city_id' => $this->city->id, 'address' => 'Старый адрес']);

        Livewire::test(EditDeliveryPoint::class, ['record' => $point->id])
            ->fillForm([
                'city_id' => $this->city->id,
                'address' => 'Новый адрес',
                'pickup_from_truck' => true,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('delivery_points', ['id' => $point->id, 'address' => 'Новый адрес']);
    }

    public function test_delete_point_removes_record(): void
    {
        $point = DeliveryPoint::create(['city_id' => $this->city->id, 'address' => 'ул. Ленина, 1']);

        Livewire::test(ListDeliveryPoints::class)
            ->callTableAction('delete', $point);

        $this->assertDatabaseMissing('delivery_points', ['id' => $point->id]);
    }
}
