<?php

namespace App\Services\Catalog;

use App\Models\Delivery\CityDeliveryTime;
use App\Models\Delivery\DeliverySchedule;
use Illuminate\Support\Collection;

/** Расчёт срока доставки со склада до города с учётом текущего времени. */
final readonly class DeliveryTimeCalculator
{
    /**
     * Дни отгрузки со склада считает DeliveryInfoService::nextShipmentDays()
     * (каноническая реализация), здесь только запросы к БД и городская надбавка.
     */
    public function calculate(int $warehouseId, int $cityId): ?int
    {
        $schedules = DeliverySchedule::where('warehouse_id', $warehouseId)->get();

        $days = DeliveryInfoService::nextShipmentDays($schedules);
        if ($days === null) {
            return null;
        }

        $cityTime = CityDeliveryTime::where('city_id', $cityId)->first();
        if ($cityTime === null) {
            return null;
        }

        return $days + $cityTime->delivery_days;
    }

    /**
     * Массовый расчёт сроков доставки — без запросов к БД.
     *
     * @param  list<int>  $warehouseIds
     * @param  Collection<(int|string), iterable<DeliverySchedule>>  $schedulesByWarehouse
     * @return array<int, int|null>
     */
    public function calculateAll(array $warehouseIds, int $cityId, mixed $schedulesByWarehouse): array
    {
        $cityTime = CityDeliveryTime::where('city_id', $cityId)->first();
        if ($cityTime === null) {
            return array_fill_keys($warehouseIds, null);
        }

        $result = [];
        foreach ($warehouseIds as $warehouseId) {
            /** @var \Illuminate\Database\Eloquent\Collection<int, DeliverySchedule>|null $schedules */
            $schedules = $schedulesByWarehouse->get($warehouseId);
            $days = DeliveryInfoService::nextShipmentDays($schedules);
            $result[$warehouseId] = $days !== null ? $days + $cityTime->delivery_days : null;
        }

        return $result;
    }

    /**
     * Стабильный диапазон доставки по расписанию, не зависящий от текущего дня:
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
