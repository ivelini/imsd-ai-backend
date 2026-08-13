<?php

namespace App\Observers;

use App\Models\Delivery\CityPriceRule;
use App\Services\Cache\Catalog\TireFilterValuesCacheService;

/** Инвалидация кеша фасетов фильтра при изменении наценки города. */
final readonly class CityPriceRuleObserver
{
    public function __construct(
        private TireFilterValuesCacheService $tireFilterCache,
    ) {}

    public function saved(CityPriceRule $rule): void
    {
        $this->tireFilterCache->forget();
    }

    public function deleted(CityPriceRule $rule): void
    {
        $this->tireFilterCache->forget();
    }
}
