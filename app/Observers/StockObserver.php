<?php

namespace App\Observers;

use App\Models\Catalog\Warehouse\Stock;
use App\Services\Cache\Catalog\TireFilterValuesCacheService;
use App\Services\Cache\Catalog\TireListCacheService;

/** Инвалидация кеша фасетов фильтра и листинга при изменении остатка. */
final readonly class StockObserver
{
    public function __construct(
        private TireFilterValuesCacheService $tireFilterCache,
        private TireListCacheService $tireListCache,
    ) {}

    public function saved(Stock $stock): void
    {
        $this->tireFilterCache->forget();
        $this->tireListCache->forget();
    }

    public function deleted(Stock $stock): void
    {
        $this->tireFilterCache->forget();
        $this->tireListCache->forget();
    }
}
