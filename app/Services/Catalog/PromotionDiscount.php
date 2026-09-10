<?php

namespace App\Services\Catalog;

/** Формула скидки по типу акции (чистая функция, ADR 0001). */
final class PromotionDiscount
{
    /**
     * Цена со скидкой. `gift` цену не меняет — подарок к покупке (FR-7.4).
     * Итог не бывает отрицательным: скидка больше цены обнуляет её.
     */
    public static function apply(float $basePrice, string $type, ?float $value): float
    {
        if ($value === null) {
            return $basePrice;
        }

        $discounted = match ($type) {
            'percent' => $basePrice * (1 - $value / 100),
            'fixed' => $basePrice - $value,
            'special' => $value,
            default => $basePrice,
        };

        return round(max(0.0, $discounted), 2);
    }
}
