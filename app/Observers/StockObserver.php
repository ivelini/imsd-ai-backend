<?php

namespace App\Observers;

use App\Models\Catalog\Warehouse\Stock;
use App\Services\Cache\Catalog\TireFilterValuesCacheService;
use App\Services\Cache\Catalog\TireListCacheService;
use App\Services\Cache\Catalog\WheelFilterValuesCacheService;
use App\Services\Cache\Catalog\WheelListCacheService;

/** Инвалидация кеша фасетов фильтра и листинга (шины и диски) при изменении остатка. */
final readonly class StockObserver
{
    public function __construct(
        private TireFilterValuesCacheService $tireFilterCache,
        private TireListCacheService $tireListCache,
        private WheelFilterValuesCacheService $wheelFilterCache,
        private WheelListCacheService $wheelListCache,
    ) {}

    public function saved(Stock $stock): void
    {
        $this->tireFilterCache->forget();
        $this->tireListCache->forget();
        $this->wheelFilterCache->forget();
        $this->wheelListCache->forget();
    }

    public function deleted(Stock $stock): void
    {
        $this->tireFilterCache->forget();
        $this->tireListCache->forget();
        $this->wheelFilterCache->forget();
        $this->wheelListCache->forget();
    }
}
