<?php

namespace Tests\Feature\Booking;

use App\Models\Booking\Slot;
use App\Models\System\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingSlotsApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::create(['key' => 'min_lead_time_h', 'value' => '1']);
        Setting::create(['key' => 'booking_horizon_days', 'value' => '30']);
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    private function slot(string $date, int $hour, bool $closed = false): void
    {
        Slot::create(['date' => $date, 'hour' => $hour, 'is_closed' => $closed]);
    }

    public function test_days_map_marks_only_days_with_selectable_slot(): void
    {
        $this->travelTo('2026-09-08 10:30:00');
        $this->slot('2026-09-08', 10); // открытый, но час прошёл границу окна (10:00 < 11:30)
        $this->slot('2026-09-09', 14); // открытый и доступен
        $this->slot('2026-09-10', 13, closed: true); // только закрытый

        $this->getJson('/api/booking/slots?date_from=2026-09-08&date_to=2026-09-10')
            ->assertOk()
            ->assertJson(['data' => ['days' => [
                '2026-09-08' => false,
                '2026-09-09' => true,
                '2026-09-10' => false,
            ]]]);
    }

    public function test_day_slots_returns_hours_with_closed_marked(): void
    {
        $this->travelTo('2026-09-08 10:30:00');
        $this->slot('2026-09-09', 10);
        $this->slot('2026-09-09', 11);
        $this->slot('2026-09-09', 13, closed: true);

        $this->getJson('/api/booking/slots/2026-09-09')
            ->assertOk()
            ->assertJson(['data' => ['slots' => [
                ['hour' => 10, 'is_closed' => false],
                ['hour' => 11, 'is_closed' => false],
                ['hour' => 13, 'is_closed' => true],
            ]]]);
    }

    public function test_rejects_invalid_dates(): void
    {
        $this->travelTo('2026-09-08 10:30:00');

        $this->getJson('/api/booking/slots?date_from=мусор&date_to=2026-09-10')
            ->assertUnprocessable();

        // формат верный, даты не существует — 422, а не 500
        $this->getJson('/api/booking/slots/9999-99-99')
            ->assertUnprocessable();
    }
}
