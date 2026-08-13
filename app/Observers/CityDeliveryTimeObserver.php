<?php

namespace App\Observers;

use App\Models\Delivery\CityDeliveryTime;
use App\Services\Cache\Catalog\TireFilterValuesCacheService;

/** Инвалидация кеша фасетов фильтра при изменении сроков доставки города. */
final readonly class CityDeliveryTimeObserver
{
    public function __construct(
        private TireFilterValuesCacheService $tireFilterCache,
    ) {}

    public function saved(CityDeliveryTime $cityDeliveryTime): void
    {
        $this->tireFilterCache->forget();
    }

    public function deleted(CityDeliveryTime $cityDeliveryTime): void
    {
        $this->tireFilterCache->forget();
    }
}
