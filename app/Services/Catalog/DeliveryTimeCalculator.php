<?php

namespace App\Services\Catalog;

use App\Models\Delivery\CityDeliveryTime;
use App\Models\Delivery\DeliverySchedule;

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
}
