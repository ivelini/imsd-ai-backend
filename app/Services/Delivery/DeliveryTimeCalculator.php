<?php

namespace App\Services\Delivery;

use App\Models\Delivery\DeliverySchedule;
use Illuminate\Support\Collection;

/** Стабильный диапазон доставки по расписанию склада (чистая функция, ADR 0001). */
final readonly class DeliveryTimeCalculator
{
    /**
     * Диапазон доставки по расписанию, не зависящий от текущего дня:
     * min = минимальный days_before, max = максимальный days_after по всем дням недели.
     * Используется для предрасчитанной таблицы catalog_prices.
     *
     * @param  Collection<int, DeliverySchedule>|null  $schedules
     * @return array{min: int, max: int}|null
     */
    public static function deliveryRange(?Collection $schedules): ?array
    {
        if ($schedules === null || $schedules->isEmpty()) {
            return null;
        }

        return [
            'min' => (int) $schedules->min('days_before'),
            'max' => (int) $schedules->max('days_after'),
        ];
    }
}
