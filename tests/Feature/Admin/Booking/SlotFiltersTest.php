<?php

namespace Tests\Feature\Admin\Booking;

use App\Enums\Booking\BookingStatus;
use App\Filament\Clusters\Booking\Resources\Slots\Pages\ListSlots;
use App\Models\Booking\Booking;
use App\Models\Booking\Slot;
use App\Models\User;
use DateTimeInterface;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesAdmin;
use Tests\TestCase;

/** Листинг слотов: период (сегодня/завтра/недели), состояние и колонка клиентов. */
class SlotFiltersTest extends TestCase
{
    use CreatesAdmin, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->createAdmin(), 'admin');

        // Тест без HTTP-запроса: middleware панели не выполняется, панель задаётся явно.
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_default_page_shows_today_slots_only(): void
    {
        $today = $this->slotAt(now());
        $tomorrow = $this->slotAt(now()->addDay());

        Livewire::test(ListSlots::class)
            ->assertCanSeeTableRecords([$today])
            ->assertCanNotSeeTableRecords([$tomorrow]);
    }

    /** Сброс фильтров — не возврат к «Сегодня», а вся сетка: сегодняшний день и дальше. */
    public function test_reset_shows_all_slots(): void
    {
        $today = $this->slotAt(now());
        $tomorrow = $this->slotAt(now()->addDay());
        $far = $this->slotAt(now()->addDays(20));

        Livewire::test(ListSlots::class)
            ->call('resetTableFiltersForm')
            ->assertCanSeeTableRecords([$today, $tomorrow, $far]);
    }

    /** Прошедшие дни сетки листинг не показывает — ни фильтром периода, ни после сброса. */
    public function test_past_slots_are_hidden(): void
    {
        $yesterday = $this->slotAt(now()->subDay());

        Livewire::test(ListSlots::class)
            ->call('resetTableFiltersForm')
            ->assertCanNotSeeTableRecords([$yesterday]);
    }

    public function test_all_slots_sorted_by_date_ascending(): void
    {
        $today = $this->slotAt(now());
        $week = $this->slotAt(now()->addDays(8));
        $far = $this->slotAt(now()->addDays(20));

        Livewire::test(ListSlots::class)
            ->call('resetTableFiltersForm')
            ->assertCanSeeTableRecords([$today, $week, $far], inOrder: true);
    }

    public function test_tomorrow_filter_narrows_table(): void
    {
        $today = $this->slotAt(now());
        $tomorrow = $this->slotAt(now()->addDay());

        Livewire::test(ListSlots::class)
            ->filterTable('period', 'tomorrow')
            ->assertCanSeeTableRecords([$tomorrow])
            ->assertCanNotSeeTableRecords([$today]);
    }

    /** Прошедшие дни листинг срезает, поэтому «текущая неделя» = сегодня…воскресенье. */
    public function test_current_week_filter_covers_week_until_sunday(): void
    {
        $sunday = $this->slotAt(now()->endOfWeek());
        $nextMonday = $this->slotAt(now()->startOfWeek()->addWeek());

        Livewire::test(ListSlots::class)
            ->filterTable('period', 'current_week')
            ->assertCanSeeTableRecords([$sunday])
            ->assertCanNotSeeTableRecords([$nextMonday]);
    }

    public function test_next_week_filter_narrows_table(): void
    {
        $today = $this->slotAt(now());
        $nextMonday = $this->slotAt(now()->startOfWeek()->addWeek());

        Livewire::test(ListSlots::class)
            ->filterTable('period', 'next_week')
            ->assertCanSeeTableRecords([$nextMonday])
            ->assertCanNotSeeTableRecords([$today]);
    }

    public function test_closed_state_filter_narrows_table(): void
    {
        $closed = $this->slotAt(now(), hour: 10, closed: true);
        $open = $this->slotAt(now(), hour: 11);

        Livewire::test(ListSlots::class)
            ->filterTable('is_closed', true)
            ->assertCanSeeTableRecords([$closed])
            ->assertCanNotSeeTableRecords([$open]);
    }

    /**
     * Список — ровно записи своего слота: клиент соседнего слота в состояние колонки не попадает.
     * Соседняя строка видна в таблице, поэтому проверяется состояние колонки по записи, а не страница.
     */
    public function test_clients_column_lists_slot_bookings(): void
    {
        $slot = $this->slotAt(now(), hour: 10);
        $otherSlot = $this->slotAt(now(), hour: 11);

        $ivan = $this->book($slot, 'Иван');
        $peter = $this->book($slot, 'Пётр');
        $this->book($otherSlot, 'Сергей');

        Livewire::test(ListSlots::class)
            ->assertTableColumnStateSet('clients', [
                "10:00 : Иван — {$ivan->user->phone}",
                "10:00 : Пётр — {$peter->user->phone}",
            ], $slot);
    }

    public function test_clients_column_skips_cancelled_bookings(): void
    {
        $slot = $this->slotAt(now());

        $this->book($slot, 'Иван');
        $this->book($slot, 'Пётр', BookingStatus::Cancelled);

        Livewire::test(ListSlots::class)
            ->assertSee('Иван')
            ->assertDontSee('Пётр');
    }

    private function slotAt(DateTimeInterface $date, int $hour = 10, bool $closed = false): Slot
    {
        return Slot::create([
            'date' => $date->format('Y-m-d'),
            'hour' => $hour,
            'is_closed' => $closed,
        ]);
    }

    private function book(Slot $slot, string $client, BookingStatus $status = BookingStatus::Confirmed): Booking
    {
        return Booking::factory()->forSlot($slot)->create([
            'user_id' => User::factory()->bookingClient()->create(['name' => $client])->id,
            'status' => $status,
        ]);
    }
}
