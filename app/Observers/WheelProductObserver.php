<?php

namespace App\Observers;

use App\Models\Catalog\Wheel\WheelProduct;
use App\Services\Cache\Catalog\WheelFilterValuesCacheService;
use App\Services\Cache\Catalog\WheelListCacheService;

/** Инвалидация кеша фасетов фильтра и листинга при изменении диска. */
final readonly class WheelProductObserver
{
    public function __construct(
        private WheelFilterValuesCacheService $wheelFilterCache,
        private WheelListCacheService $wheelListCache,
    ) {}

    public function saved(WheelProduct $wheelProduct): void
    {
        $this->wheelFilterCache->forget();
        $this->wheelListCache->forget();
    }

    public function deleted(WheelProduct $wheelProduct): void
    {
        $this->wheelFilterCache->forget();
        $this->wheelListCache->forget();
    }
}
