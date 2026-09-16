<?php

namespace Tests\Feature\Admin\Booking;

use App\Enums\Booking\BookingStatus;
use App\Enums\Booking\SlotPeriod;
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

/** Листинг слотов: период (пресеты и даты), состояние и колонка клиентов. */
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

    /** Стартовый вид — текущая неделя целиком: воскресенье её же, понедельник следующей — уже нет. */
    public function test_default_page_shows_current_week(): void
    {
        $today = $this->slotAt(now());
        $sunday = $this->slotAt(now()->endOfWeek(), hour: 11);
        $nextMonday = $this->slotAt(now()->startOfWeek()->addWeek(), hour: 11);

        Livewire::test(ListSlots::class)
            ->assertCanSeeTableRecords([$today, $sunday])
            ->assertCanNotSeeTableRecords([$nextMonday]);
    }

    /** Дефолт виден и в самом фильтре: иначе сужение таблицы выглядит ничем не объяснённым. */
    public function test_default_page_fills_period_filter(): void
    {
        Livewire::test(ListSlots::class)
            ->assertSet('tableFilters.period.from', now()->startOfWeek()->toDateString())
            ->assertSet('tableFilters.period.until', now()->endOfWeek()->toDateString())
            ->assertSet('tableFilters.period.preset', SlotPeriod::CurrentWeek->value);
    }

    /** Сброс фильтров — не возврат к дефолту, а вся сетка: сегодняшний день и дальше. */
    public function test_reset_shows_all_slots(): void
    {
        $today = $this->slotAt(now());
        $tomorrow = $this->slotAt(now()->addDay());
        $far = $this->slotAt(now()->addDays(20));

        Livewire::test(ListSlots::class)
            ->call('resetTableFiltersForm')
            ->assertSet('tableFilters.period.from', null)
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

    /** Заполнено только «С» — это один день, а не «от этой даты и дальше». */
    public function test_single_date_filter_shows_only_that_day(): void
    {
        $day = now()->addDays(10);
        $slot = $this->slotAt($day);
        $nextDaySlot = $this->slotAt($day->copy()->addDay());

        Livewire::test(ListSlots::class)
            ->filterTable('period', ['from' => $day->toDateString(), 'until' => null])
            ->assertCanSeeTableRecords([$slot])
            ->assertCanNotSeeTableRecords([$nextDaySlot]);
    }

    /** Обе границы включительно: последний день диапазона виден, следующий за ним — нет. */
    public function test_date_range_filter_includes_both_ends(): void
    {
        $from = now()->addDays(10);
        $last = $from->copy()->addDays(2);

        $firstSlot = $this->slotAt($from);
        $lastSlot = $this->slotAt($last);
        $outsideSlot = $this->slotAt($last->copy()->addDay());

        Livewire::test(ListSlots::class)
            ->filterTable('period', ['from' => $from->toDateString(), 'until' => $last->toDateString()])
            ->assertCanSeeTableRecords([$firstSlot, $lastSlot])
            ->assertCanNotSeeTableRecords([$outsideSlot]);
    }

    /** Пустой период сетку не сужает: пустые поля читаются как «показывай всё». */
    public function test_empty_period_shows_whole_grid(): void
    {
        $today = $this->slotAt(now());
        $far = $this->slotAt(now()->addDays(20));

        Livewire::test(ListSlots::class)
            ->filterTable('period', ['from' => null, 'until' => null])
            ->assertCanSeeTableRecords([$today, $far]);
    }

    /** Пресет заполняет обе даты и сразу сужает таблицу — это и есть «быстрый выбор». */
    public function test_period_preset_fills_dates_and_narrows_table(): void
    {
        $today = $this->slotAt(now());
        $tomorrow = $this->slotAt(now()->addDay());

        Livewire::test(ListSlots::class)
            ->set('tableFilters.period.preset', SlotPeriod::Tomorrow->value)
            ->assertSet('tableFilters.period.from', now()->addDay()->toDateString())
            ->assertSet('tableFilters.period.until', now()->addDay()->toDateString())
            ->assertCanSeeTableRecords([$tomorrow])
            ->assertCanNotSeeTableRecords([$today]);
    }

    /** Правка даты руками снимает подсветку пресета: иначе панель показывает пресет, которого в фильтре нет. */
    public function test_manual_date_clears_preset(): void
    {
        $day = now()->addDays(10);

        Livewire::test(ListSlots::class)
            ->set('tableFilters.period.preset', SlotPeriod::Today->value)
            ->set('tableFilters.period.from', $day->toDateString())
            ->assertSet('tableFilters.period.preset', null)
            ->assertSet('tableFilters.period.from', $day->toDateString());
    }

    /** По умолчанию на странице 50 строк: из 55 записей 51-я уходит на вторую страницу. */
    public function test_default_page_size_is_fifty(): void
    {
        $firstDay = now()->startOfDay();

        // 11 часов × 5 дней = 55 слотов; порядок — как в таблице (по дате, затем по часу).
        $slots = collect(range(0, 4))
            ->flatMap(fn (int $day): array => collect(range(8, 18))
                ->map(fn (int $hour): Slot => $this->slotAt($firstDay->copy()->addDays($day), hour: $hour))
                ->all());

        Livewire::test(ListSlots::class)
            ->filterTable('period', [
                'from' => $firstDay->toDateString(),
                'until' => $firstDay->copy()->addDays(4)->toDateString(),
            ])
            ->assertCanSeeTableRecords($slots->take(50)->all(), inOrder: true)
            ->assertCanNotSeeTableRecords([$slots->last()]);
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
