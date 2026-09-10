<?php

namespace App\DTOs\Catalog;

use Illuminate\Support\Collection;

/** Справочники для пересчёта catalog_prices: наценки городов, сроки, расписания, акции. */
final readonly class RecalcContext
{
    /**
     * @param  Collection<array-key, Collection<int, array<string, float>>>  $cityRules
     * @param  Collection<array-key, array{min: int, max: int}|null>  $deliveryByWarehouse
     * @param  Collection<array-key, int>  $cityDeliveryDays
     * @param  array<int, array<string, mixed>>  $promotions  активные акции
     * @param  array<string, int|null>  $brandIdsByProduct  brand_id по ключу «morph-тип:id»
     */
    public function __construct(
        public Collection $cityRules,
        public Collection $deliveryByWarehouse,
        public Collection $cityDeliveryDays,
        public array $promotions = [],
        public array $brandIdsByProduct = [],
    ) {}
}
