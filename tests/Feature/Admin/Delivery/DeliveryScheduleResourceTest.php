<?php

namespace Tests\Feature\Admin\Delivery;

use App\Filament\Resources\DeliverySchedules\Pages\CreateDeliverySchedule;
use App\Filament\Resources\DeliverySchedules\Pages\EditDeliverySchedule;
use App\Filament\Resources\DeliverySchedules\Pages\ListDeliverySchedules;
use App\Models\Auth\Admin;
use App\Models\Catalog\Warehouse\Warehouse;
use App\Models\Delivery\DeliverySchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesAdmin;
use Tests\TestCase;

/** DeliveryScheduleResource панели: CRUD графиков отгрузки. */
class DeliveryScheduleResourceTest extends TestCase
{
    use CreatesAdmin, RefreshDatabase;

    private Admin $admin;

    private Warehouse $warehouse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->createAdmin();

        $this->warehouse = Warehouse::factory()->create(['name' => 'Склад']);

        $this->actingAs($this->admin, 'admin');
    }

    public function test_create_schedule_validates_and_saves(): void
    {
        Livewire::test(CreateDeliverySchedule::class)
            ->fillForm([
                'warehouse_id' => $this->warehouse->id,
                'day_of_week' => 1,
                'cutoff_time' => '14:00',
                'days_before' => 1,
                'days_after' => 3,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('delivery_schedules', [
            'warehouse_id' => $this->warehouse->id,
            'day_of_week' => 1,
            'cutoff_time' => '14:00',
        ]);
    }

    public function test_update_schedule_changes_fields(): void
    {
        $schedule = DeliverySchedule::create([
            'warehouse_id' => $this->warehouse->id,
            'day_of_week' => 1,
            'cutoff_time' => '14:00',
            'days_before' => 1,
            'days_after' => 3,
        ]);

        Livewire::test(EditDeliverySchedule::class, ['record' => $schedule->id])
            ->fillForm([
                'warehouse_id' => $this->warehouse->id,
                'day_of_week' => 5,
                'cutoff_time' => '10:30',
                'days_before' => 0,
                'days_after' => 2,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('delivery_schedules', ['id' => $schedule->id, 'day_of_week' => 5, 'cutoff_time' => '10:30']);
    }

    public function test_delete_schedule_removes_record(): void
    {
        $schedule = DeliverySchedule::create([
            'warehouse_id' => $this->warehouse->id,
            'day_of_week' => 1,
            'cutoff_time' => '14:00',
            'days_before' => 1,
            'days_after' => 3,
        ]);

        Livewire::test(ListDeliverySchedules::class)
            ->callTableAction('delete', $schedule);

        $this->assertDatabaseMissing('delivery_schedules', ['id' => $schedule->id]);
    }
}
