<?php

namespace App\Observers;

use App\Models\Delivery\DeliverySchedule;
use App\Services\Cache\Catalog\TireFilterValuesCacheService;
use App\Services\Cache\Catalog\TireListCacheService;

/** Инвалидация кеша фасетов фильтра и листинга при изменении графика отгрузки. */
final readonly class DeliveryScheduleObserver
{
    public function __construct(
        private TireFilterValuesCacheService $tireFilterCache,
        private TireListCacheService $tireListCache,
    ) {}

    public function saved(DeliverySchedule $schedule): void
    {
        $this->tireFilterCache->forget();
        $this->tireListCache->forget();
    }

    public function deleted(DeliverySchedule $schedule): void
    {
        $this->tireFilterCache->forget();
        $this->tireListCache->forget();
    }
}
