<?php

namespace App\Services\Catalog;

use App\Models\Delivery\CityPriceRule;

/** Расчёт стоимости доставки по городу для финальной цены. */
final readonly class DeliveryCostCalculator
{
    /**
     * Наценка города (city_price_rules) для финальной цены — единый матчер
     * с пересчётом catalog_prices (MarkupRuleMatcher). Нет правила → null.
     */
    public function calculate(int $cityId, ?float $finalPrice): ?float
    {
        if ($finalPrice === null) {
            return null;
        }

        $rules = CityPriceRule::where('city_id', $cityId)
            ->get()
            ->map(fn (CityPriceRule $rule) => [
                'price_from' => (float) $rule->price_from,
                'price_to' => (float) $rule->price_to,
                'markup' => (float) $rule->markup,
            ])
            ->all();

        $rule = MarkupRuleMatcher::match($finalPrice, $rules);

        return $rule !== null ? (float) $rule['markup'] : null;
    }
}
