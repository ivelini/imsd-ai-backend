<?php

namespace App\Observers;

use App\Models\Delivery\DeliverySchedule;
use App\Services\Cache\Catalog\TireFilterValuesCacheService;

/** Инвалидация кеша фасетов фильтра при изменении графика отгрузки. */
final readonly class DeliveryScheduleObserver
{
    public function __construct(
        private TireFilterValuesCacheService $tireFilterCache,
    ) {}

    public function saved(DeliverySchedule $schedule): void
    {
        $this->tireFilterCache->forget();
    }

    public function deleted(DeliverySchedule $schedule): void
    {
        $this->tireFilterCache->forget();
    }
}
