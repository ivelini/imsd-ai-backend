<?php

namespace App\Services\Catalog;

use App\Models\Delivery\CityPriceRule;

/** Расчёт стоимости доставки по городу для финальной цены. */
final readonly class DeliveryCostCalculator
{
    /**
     * Ищем CityPriceRule где price_from <= finalPrice <= price_to.
     * Нет правила или finalPrice=null → null.
     */
    public function calculate(int $cityId, ?float $finalPrice): ?float
    {
        if ($finalPrice === null) {
            return null;
        }

        $rule = CityPriceRule::where('city_id', $cityId)
            ->where('price_from', '<=', $finalPrice)
            ->where('price_to', '>=', $finalPrice)
            ->first();

        return $rule?->markup;
    }
}
