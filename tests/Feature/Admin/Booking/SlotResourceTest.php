<?php

namespace Tests\Feature\Admin\Booking;

use App\Filament\Clusters\Booking\Resources\Slots\Pages\ListSlots;
use App\Models\Booking\Slot;
use Database\Seeders\BookingScheduleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesAdmin;
use Tests\TestCase;

/** SlotResource панели: закрытие/открытие слота и генерация сетки. */
class SlotResourceTest extends TestCase
{
    use CreatesAdmin, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->createAdmin(), 'admin');
    }

    public function test_toggle_close_action_closes_and_reopens_slot(): void
    {
        $slot = Slot::create(['date' => now()->toDateString(), 'hour' => 10]);

        Livewire::test(ListSlots::class)
            ->callTableAction('closeSlot', $slot);

        $this->assertTrue($slot->fresh()->is_closed);

        Livewire::test(ListSlots::class)
            ->callTableAction('closeSlot', $slot->fresh());

        $this->assertFalse($slot->fresh()->is_closed);
    }

    public function test_generate_grid_action_creates_slots(): void
    {
        $this->seed(BookingScheduleSeeder::class);

        Livewire::test(ListSlots::class)
            ->callAction('generateGrid');

        // Пн–Сб × 10 часов, горизонт по умолчанию 30 дней — сетка не пустая и открытая
        $this->assertGreaterThan(0, Slot::count());
        $this->assertSame(0, Slot::where('is_closed', true)->count());
    }
}
