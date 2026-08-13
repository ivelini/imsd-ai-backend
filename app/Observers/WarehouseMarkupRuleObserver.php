<?php

namespace App\Observers;

use App\Models\Catalog\MarkupRule\WarehouseMarkupRule;
use App\Services\Cache\Catalog\TireFilterValuesCacheService;

/** Инвалидация кеша фасетов фильтра при изменении наценки склада. */
final readonly class WarehouseMarkupRuleObserver
{
    public function __construct(
        private TireFilterValuesCacheService $tireFilterCache,
    ) {}

    public function saved(WarehouseMarkupRule $rule): void
    {
        $this->tireFilterCache->forget();
    }

    public function deleted(WarehouseMarkupRule $rule): void
    {
        $this->tireFilterCache->forget();
    }
}
