<?php

namespace App\Observers;

use App\Models\Catalog\Tire\TireProduct;
use App\Services\Cache\Catalog\TireFilterValuesCacheService;

/** Инвалидация кеша фасетов фильтра при изменении шины. */
final readonly class TireProductObserver
{
    public function __construct(
        private TireFilterValuesCacheService $tireFilterCache,
    ) {}

    public function saved(TireProduct $tireProduct): void
    {
        $this->tireFilterCache->forget();
    }

    public function deleted(TireProduct $tireProduct): void
    {
        $this->tireFilterCache->forget();
    }
}
