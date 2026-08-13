<?php

namespace Tests\Concerns;

use App\Models\Catalog\Warehouse\Warehouse;
use App\Models\Delivery\City;
use App\Models\Delivery\DeliverySchedule;
use App\Models\Delivery\Region;

/** Гео-фикстуры: город и расписание отгрузки склада на сегодня. */
trait CreatesCity
{
    protected function createCity(): City
    {
        $region = Region::create(['code' => '74', 'name' => 'Челябинская область']);

        return City::create(['region_id' => $region->id, 'name' => 'Челябинск', 'sort' => 1]);
    }

    protected function createScheduleForToday(Warehouse $warehouse, int $daysBefore = 2, int $daysAfter = 5): DeliverySchedule
    {
        return DeliverySchedule::create([
            'warehouse_id' => $warehouse->id,
            'day_of_week' => now()->dayOfWeekIso - 1,
            'cutoff_time' => '18:00',
            'days_before' => $daysBefore,
            'days_after' => $daysAfter,
        ]);
    }
}
