<?php

namespace Tests\Feature\Delivery;

use App\Models\Delivery\DeliverySchedule;
use App\Services\Delivery\DeliveryTimeCalculator;
use Tests\TestCase;

/** Стабильный диапазон доставки по расписанию склада (чистая функция, без БД). */
class DeliveryTimeCalculatorTest extends TestCase
{
    public function test_delivery_range_returns_weekly_envelope(): void
    {
        $schedules = collect([
            DeliverySchedule::make([
                'warehouse_id' => 1, 'day_of_week' => 0, 'cutoff_time' => '14:00', 'days_before' => 1, 'days_after' => 2,
            ]),
            DeliverySchedule::make([
                'warehouse_id' => 1, 'day_of_week' => 1, 'cutoff_time' => '14:00', 'days_before' => 1, 'days_after' => 2,
            ]),
            DeliverySchedule::make([
                'warehouse_id' => 1, 'day_of_week' => 5, 'cutoff_time' => '12:00', 'days_before' => 2, 'days_after' => 3,
            ]),
        ]);

        $this->assertSame(['min' => 1, 'max' => 3], DeliveryTimeCalculator::deliveryRange($schedules));
    }

    public function test_delivery_range_null_without_schedules(): void
    {
        $this->assertNull(DeliveryTimeCalculator::deliveryRange(collect()));
    }

    public function test_delivery_range_null_when_schedules_null(): void
    {
        $this->assertNull(DeliveryTimeCalculator::deliveryRange(null));
    }

    public function test_delivery_range_single_day(): void
    {
        $schedules = collect([
            DeliverySchedule::make([
                'warehouse_id' => 1, 'day_of_week' => 2, 'cutoff_time' => '14:00', 'days_before' => 2, 'days_after' => 3,
            ]),
        ]);

        $this->assertSame(['min' => 2, 'max' => 3], DeliveryTimeCalculator::deliveryRange($schedules));
    }
}
