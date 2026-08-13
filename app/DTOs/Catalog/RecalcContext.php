<?php

namespace App\DTOs\Catalog;

use Illuminate\Support\Collection;

/** Справочники для пересчёта catalog_prices: правила наценок, сроки, расписания. */
final readonly class RecalcContext
{
    /**
     * @param  Collection<array-key, Collection<int, array<string, int|float>>>  $warehouseRules
     * @param  Collection<array-key, Collection<int, array<string, float>>>  $cityRules
     * @param  Collection<array-key, array{min: int, max: int}|null>  $deliveryByWarehouse
     * @param  Collection<array-key, int>  $cityDeliveryDays
     */
    public function __construct(
        public Collection $warehouseRules,
        public Collection $cityRules,
        public Collection $deliveryByWarehouse,
        public Collection $cityDeliveryDays,
    ) {}
}
