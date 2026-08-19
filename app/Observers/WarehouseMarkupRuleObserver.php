<?php

namespace App\Observers;

use App\Models\Catalog\MarkupRule\WarehouseMarkupRule;
use App\Services\Cache\Catalog\TireFilterValuesCacheService;
use App\Services\Cache\Catalog\TireListCacheService;

/** Инвалидация кеша фасетов фильтра и листинга при изменении наценки склада. */
final readonly class WarehouseMarkupRuleObserver
{
    public function __construct(
        private TireFilterValuesCacheService $tireFilterCache,
        private TireListCacheService $tireListCache,
    ) {}

    public function saved(WarehouseMarkupRule $rule): void
    {
        $this->tireFilterCache->forget();
        $this->tireListCache->forget();
    }

    public function deleted(WarehouseMarkupRule $rule): void
    {
        $this->tireFilterCache->forget();
        $this->tireListCache->forget();
    }
}
