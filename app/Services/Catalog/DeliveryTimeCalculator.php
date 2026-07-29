<?php

namespace App\Services\Catalog;

use App\Models\Delivery\CityDeliveryTime;
use App\Models\Delivery\DeliverySchedule;
use Illuminate\Support\Carbon;

/** Расчёт срока доставки со склада до города с учётом текущего времени. */
final readonly class DeliveryTimeCalculator
{
    /**
     * Алгоритм:
     * 1. Текущий день недели: 0=Mon (WeekDay)
     * 2. Ищем DeliverySchedule для склада на сегодня,
     *    если нет — ищем по следующим дням недели (циклично).
     * 3. Текущее время < cutoff_time → days_before, иначе days_after.
     * 4. offset дней до ближайшего дня отгрузки + days_before/days_after + city_delivery_days.
     * 5. Нет schedule или нет city_delivery_time → null.
     */
    public function calculate(int $warehouseId, int $cityId): ?int
    {
        $now = Carbon::now();
        $todayDow = $now->dayOfWeekIso - 1; // 0=Mon
        $todayTime = $now->format('H:i');

        $schedules = DeliverySchedule::where('warehouse_id', $warehouseId)
            ->get()
            ->keyBy('day_of_week');

        if ($schedules->isEmpty()) {
            return null;
        }

        $schedule = null;
        $offset = 0;

        for ($d = 0; $d <= 7; $d++) {
            $day = ($todayDow + $d) % 7;

            if ($schedule = $schedules->get($day)) {
                $offset = $d;
                break;
            }
        }

        if ($schedule === null) {
            return null;
        }

        $scheduleDays = ($offset === 0 && $todayTime >= $schedule->cutoff_time)
            ? $schedule->days_after
            : $schedule->days_before;

        $cityTime = CityDeliveryTime::where('city_id', $cityId)->first();

        if ($cityTime === null) {
            return null;
        }

        return $scheduleDays + $offset + $cityTime->delivery_days;
    }
}
