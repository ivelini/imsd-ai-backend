<?php

namespace Tests\Feature\Admin\Booking;

use App\Filament\Clusters\Booking\Resources\Slots\Pages\EditSlot;
use App\Filament\Clusters\Booking\Resources\Slots\Pages\ListSlots;
use App\Models\Booking\Slot;
use Database\Seeders\BookingScheduleSeeder;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
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

    /** Дата и час в заголовке — на правке они не редактируются, но должны быть видны. */
    public function test_edit_page_title_shows_date_and_hour(): void
    {
        $slot = Slot::create(['date' => '2026-09-12', 'hour' => 10]);

        Livewire::test(EditSlot::class, ['record' => $slot->id])
            ->assertSee('Редактирование слота: 12.09.2026, 10:00');
    }

    public function test_edit_form_hides_date_and_hour(): void
    {
        $slot = Slot::create(['date' => '2026-09-12', 'hour' => 10]);

        Livewire::test(EditSlot::class, ['record' => $slot->id])
            ->assertFormFieldDoesNotExist('date')
            ->assertFormFieldDoesNotExist('hour');
    }

    /** Статус слота читается цветом: открыт — зелёный круг, закрыт — красный (было наоборот). */
    public function test_slot_status_column_is_green_when_open_red_when_closed(): void
    {
        $column = Livewire::test(ListSlots::class)->instance()->getTable()->getColumn('is_closed');

        $this->assertInstanceOf(IconColumn::class, $column);
        $this->assertSame('success', $column->getFalseColor());
        $this->assertSame(Heroicon::OutlinedCheckCircle, $column->getFalseIcon());
        $this->assertSame('danger', $column->getTrueColor());
        $this->assertSame(Heroicon::OutlinedXCircle, $column->getTrueIcon());
    }
}
