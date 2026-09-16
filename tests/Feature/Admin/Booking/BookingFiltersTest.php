<?php

namespace Tests\Feature\Admin\Booking;

use App\Enums\Booking\BookingStatus;
use App\Enums\Booking\SlotPeriod;
use App\Filament\Clusters\Booking\Resources\Bookings\Pages\ListBookings;
use App\Models\Booking\Booking;
use App\Models\Booking\Slot;
use DateTimeInterface;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesAdmin;
use Tests\TestCase;

/** Листинг записей: период по дате слота, статус и порядок строк. */
class BookingFiltersTest extends TestCase
{
    use CreatesAdmin, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->createAdmin(), 'admin');

        // Тест без HTTP-запроса: middleware панели не выполняется, панель задаётся явно.
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    /** Стартовый вид — текущая неделя: запись на сегодня видна, на понедельник следующей недели — нет. */
    public function test_default_page_shows_current_week_bookings(): void
    {
        $today = $this->bookingAt(now());
        $nextWeek = $this->bookingAt(now()->startOfWeek()->addWeek());

        Livewire::test(ListBookings::class)
            ->assertCanSeeTableRecords([$today])
            ->assertCanNotSeeTableRecords([$nextWeek]);
    }

    /** Дефолт виден и в самом фильтре: иначе сужение таблицы выглядит ничем не объяснённым. */
    public function test_default_page_fills_period_filter(): void
    {
        Livewire::test(ListBookings::class)
            ->assertSet('tableFilters.period.from', now()->startOfWeek()->toDateString())
            ->assertSet('tableFilters.period.until', now()->endOfWeek()->toDateString())
            ->assertSet('tableFilters.period.preset', SlotPeriod::CurrentWeek->value);
    }

    /** Сброс фильтров — все будущие записи, а не возврат к текущей неделе. */
    public function test_reset_shows_all_future_bookings(): void
    {
        $today = $this->bookingAt(now());
        $far = $this->bookingAt(now()->addDays(20));

        Livewire::test(ListBookings::class)
            ->call('resetTableFiltersForm')
            ->assertSet('tableFilters.period.from', null)
            ->assertCanSeeTableRecords([$today, $far]);
    }

    /** Заполнено только «С» — один день: сужение идёт по дате слота, а не по времени начала. */
    public function test_single_date_filter_shows_only_that_day(): void
    {
        $day = now()->addDays(10);
        $booking = $this->bookingAt($day);
        $nextDayBooking = $this->bookingAt($day->copy()->addDay());

        Livewire::test(ListBookings::class)
            ->filterTable('period', ['from' => $day->toDateString(), 'until' => null])
            ->assertCanSeeTableRecords([$booking])
            ->assertCanNotSeeTableRecords([$nextDayBooking]);
    }

    /** Обе границы включительно: последний день диапазона виден, следующий за ним — нет. */
    public function test_date_range_filter_includes_both_ends(): void
    {
        $from = now()->addDays(10);
        $last = $from->copy()->addDays(2);

        $firstBooking = $this->bookingAt($from);
        $lastBooking = $this->bookingAt($last);
        $outsideBooking = $this->bookingAt($last->copy()->addDay());

        Livewire::test(ListBookings::class)
            ->filterTable('period', ['from' => $from->toDateString(), 'until' => $last->toDateString()])
            ->assertCanSeeTableRecords([$firstBooking, $lastBooking])
            ->assertCanNotSeeTableRecords([$outsideBooking]);
    }

    /** Пресет заполняет обе даты и сразу сужает таблицу — это и есть «быстрый выбор». */
    public function test_period_preset_fills_dates_and_narrows_table(): void
    {
        $today = $this->bookingAt(now());
        $tomorrow = $this->bookingAt(now()->addDay());

        Livewire::test(ListBookings::class)
            ->set('tableFilters.period.preset', SlotPeriod::Tomorrow->value)
            ->assertSet('tableFilters.period.from', now()->addDay()->toDateString())
            ->assertSet('tableFilters.period.until', now()->addDay()->toDateString())
            ->assertCanSeeTableRecords([$tomorrow])
            ->assertCanNotSeeTableRecords([$today]);
    }

    /** Порядок — дата слота, затем время: со старой сортировкой (только время суток) завтрашние 09:00 встали бы первыми. */
    public function test_bookings_sorted_by_slot_date_then_time(): void
    {
        $today = now()->startOfDay();

        $lateToday = $this->bookingAt($today, '15:00');
        $earlyTomorrow = $this->bookingAt($today->copy()->addDay(), '09:00');
        $lateTomorrow = $this->bookingAt($today->copy()->addDay(), '15:00');

        Livewire::test(ListBookings::class)
            ->filterTable('period', ['from' => $today->toDateString(), 'until' => $today->copy()->addDay()->toDateString()])
            ->assertCanSeeTableRecords([$lateToday, $earlyTomorrow, $lateTomorrow], inOrder: true);
    }

    /** По умолчанию на странице 50 строк: из 55 записей 51-я уходит на вторую страницу. */
    public function test_default_page_size_is_fifty(): void
    {
        $firstDay = now()->startOfDay();

        // 11 часов × 5 дней = 55 записей; порядок — как в таблице (дата слота, затем время).
        $bookings = collect(range(0, 4))
            ->flatMap(fn (int $day): array => collect(range(9, 19))
                ->map(fn (int $hour): Booking => $this->bookingAt($firstDay->copy()->addDays($day), sprintf('%02d:00', $hour)))
                ->all());

        Livewire::test(ListBookings::class)
            ->filterTable('period', [
                'from' => $firstDay->toDateString(),
                'until' => $firstDay->copy()->addDays(4)->toDateString(),
            ])
            ->assertCanSeeTableRecords($bookings->take(50)->all(), inOrder: true)
            ->assertCanNotSeeTableRecords([$bookings->last()]);
    }

    public function test_status_filter_narrows_table(): void
    {
        $done = Booking::factory()->forSlot($this->slotAt(now(), hour: 10))->done()->create();
        $confirmed = Booking::factory()->forSlot($this->slotAt(now(), hour: 11))->create();

        Livewire::test(ListBookings::class)
            ->filterTable('status', BookingStatus::Done->value)
            ->assertCanSeeTableRecords([$done])
            ->assertCanNotSeeTableRecords([$confirmed]);
    }

    private function bookingAt(DateTimeInterface $date, string $time = '10:00'): Booking
    {
        return Booking::factory()->forSlot($this->slotAt($date, hour: (int) substr($time, 0, 2)))->create();
    }

    private function slotAt(DateTimeInterface $date, int $hour): Slot
    {
        return Slot::create([
            'date' => $date->format('Y-m-d'),
            'hour' => $hour,
            'is_closed' => false,
        ]);
    }
}
