<?php

namespace App\Observers;

use App\Models\Delivery\CityPriceRule;
use App\Services\Cache\Catalog\TireFilterValuesCacheService;
use App\Services\Cache\Catalog\TireListCacheService;
use App\Services\Cache\Catalog\WheelFilterValuesCacheService;
use App\Services\Cache\Catalog\WheelListCacheService;

/** Инвалидация кеша фасетов фильтра и листинга при изменении наценки города. */
final readonly class CityPriceRuleObserver
{
    public function __construct(
        private TireFilterValuesCacheService $tireFilterCache,
        private TireListCacheService $tireListCache,
        private WheelFilterValuesCacheService $wheelFilterCache,
        private WheelListCacheService $wheelListCache,
    ) {}

    public function saved(CityPriceRule $rule): void
    {
        $this->tireFilterCache->forget();
        $this->tireListCache->forget();
        $this->wheelFilterCache->forget();
        $this->wheelListCache->forget();
    }

    public function deleted(CityPriceRule $rule): void
    {
        $this->tireFilterCache->forget();
        $this->tireListCache->forget();
        $this->wheelFilterCache->forget();
        $this->wheelListCache->forget();
    }
}
