<?php

namespace App\Services\Catalog;

/**
 * Поиск правила наценки, покрывающего цену (чистая функция, без БД).
 * Единый источник правила выбора: побеждает наименьший price_from, затем price_to.
 */
final class MarkupRuleMatcher
{
    /**
     * Правило — массив с ключами price_from/price_to (и любым полем-значением:
     * coefficient для складов, markup для городов).
     *
     * @param  array<int, array{price_from: int|float, price_to: int|float, ...}>  $rules
     * @return array{price_from: int|float, price_to: int|float, ...}|null
     */
    public static function match(float $purchasePrice, array $rules): ?array
    {
        if ($rules === []) {
            return null;
        }

        usort(
            $rules,
            fn (array $a, array $b) => [$a['price_from'], $a['price_to']] <=> [$b['price_from'], $b['price_to']],
        );

        foreach ($rules as $rule) {
            if ($purchasePrice >= $rule['price_from'] && $purchasePrice <= $rule['price_to']) {
                return $rule;
            }
        }

        return null;
    }
}
