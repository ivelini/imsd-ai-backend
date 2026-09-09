<?php

namespace Tests\Unit\Services\Delivery;

use App\Models\Delivery\DeliverySchedule;
use App\Services\Delivery\DeliveryInfoService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

/** Ближайшая отгрузка со склада от текущего момента: days + day_of_week (0=Mon…6=Sun). */
class DeliveryNextShipmentTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_next_shipment_today_before_cutoff(): void
    {
        Carbon::setTestNow(Carbon::now()->startOfWeek()->setTime(10, 0)); // пн 10:00

        $schedules = $this->schedules([$this->schedule(0, '16:00', 2, 3)]);

        $this->assertSame(['days' => 2, 'day_of_week' => 0], DeliveryInfoService::nextShipment($schedules));
    }

    public function test_next_shipment_after_cutoff_uses_days_after(): void
    {
        Carbon::setTestNow(Carbon::now()->startOfWeek()->setTime(18, 0)); // пн 18:00 — после cutoff

        $schedules = $this->schedules([$this->schedule(0, '16:00', 2, 3)]);

        $this->assertSame(['days' => 3, 'day_of_week' => 0], DeliveryInfoService::nextShipment($schedules));
    }

    public function test_next_shipment_returns_future_day_of_week(): void
    {
        Carbon::setTestNow(Carbon::now()->startOfWeek()->addDay()->setTime(10, 0)); // вт 10:00

        $schedules = $this->schedules([$this->schedule(3, '16:00', 2, 3)]); // чт

        $this->assertSame(['days' => 4, 'day_of_week' => 3], DeliveryInfoService::nextShipment($schedules));
    }

    public function test_next_shipment_returns_null_without_schedules(): void
    {
        $this->assertNull(DeliveryInfoService::nextShipment(null));
        $this->assertNull(DeliveryInfoService::nextShipment(new Collection));
    }

    /** @return array{day_of_week: int, cutoff_time: string, days_before: int, days_after: int} */
    private function schedule(int $dayOfWeek, string $cutoffTime, int $daysBefore, int $daysAfter): array
    {
        return [
            'day_of_week' => $dayOfWeek,
            'cutoff_time' => $cutoffTime,
            'days_before' => $daysBefore,
            'days_after' => $daysAfter,
        ];
    }

    /** @param  list<array{day_of_week: int, cutoff_time: string, days_before: int, days_after: int}>  $data */
    private function schedules(array $data): Collection
    {
        return new Collection(array_map(
            fn (array $attrs): DeliverySchedule => new DeliverySchedule($attrs),
            $data,
        ));
    }
}
