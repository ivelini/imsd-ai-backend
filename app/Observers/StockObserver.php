<?php

namespace App\Observers;

use App\Models\Catalog\Warehouse\Stock;
use App\Services\Cache\Catalog\TireFilterValuesCacheService;

/** Инвалидация кеша фасетов фильтра при изменении остатка. */
final readonly class StockObserver
{
    public function __construct(
        private TireFilterValuesCacheService $tireFilterCache,
    ) {}

    public function saved(Stock $stock): void
    {
        $this->tireFilterCache->forget();
    }

    public function deleted(Stock $stock): void
    {
        $this->tireFilterCache->forget();
    }
}
