<?php

namespace App\Observers;

use App\Models\Delivery\CityDeliveryTime;
use App\Services\Cache\Catalog\TireFilterValuesCacheService;
use App\Services\Cache\Catalog\TireListCacheService;
use App\Services\Cache\Catalog\WheelFilterValuesCacheService;
use App\Services\Cache\Catalog\WheelListCacheService;

/** Инвалидация кеша фасетов фильтра и листинга при изменении сроков доставки города. */
final readonly class CityDeliveryTimeObserver
{
    public function __construct(
        private TireFilterValuesCacheService $tireFilterCache,
        private TireListCacheService $tireListCache,
        private WheelFilterValuesCacheService $wheelFilterCache,
        private WheelListCacheService $wheelListCache,
    ) {}

    public function saved(CityDeliveryTime $cityDeliveryTime): void
    {
        $this->tireFilterCache->forget();
        $this->tireListCache->forget();
        $this->wheelFilterCache->forget();
        $this->wheelListCache->forget();
    }

    public function deleted(CityDeliveryTime $cityDeliveryTime): void
    {
        $this->tireFilterCache->forget();
        $this->tireListCache->forget();
        $this->wheelFilterCache->forget();
        $this->wheelListCache->forget();
    }
}
