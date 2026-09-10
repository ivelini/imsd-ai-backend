<?php

namespace App\Services\Catalog;

use Carbon\CarbonInterface;

/**
 * Выбор акции для товара (чистая функция, ADR 0001).
 *
 * Приоритет по конкретике привязки: конкретный товар → бренд → весь каталог;
 * внутри одного уровня побеждает акция с большей скидкой в рублях (FR ADM-7.1.5).
 */
final class PromotionMatcher
{
    /**
     * @param  array<int, array<string, mixed>>  $promotions  акции: type, value, promotable_type, promotable_id, starts_at, ends_at
     * @return array<string, mixed>|null выбранная активная акция
     */
    public static function match(
        float $basePrice,
        array $promotions,
        string $productType,
        int $productId,
        ?int $brandId,
        CarbonInterface $now,
    ): ?array {
        $best = null;
        $bestScope = null;
        $bestDiscount = null;

        foreach ($promotions as $promotion) {
            $scope = self::scopeOf($promotion, $productType, $productId, $brandId);

            if ($scope === null || ! self::isActive($promotion, $now)) {
                continue;
            }

            $discount = $basePrice - PromotionDiscount::apply(
                $basePrice,
                (string) $promotion['type'],
                isset($promotion['value']) ? (float) $promotion['value'] : null,
            );

            // Меньший scope конкретнее; при равном — большая скидка.
            if ($bestScope === null
                || $scope < $bestScope
                || ($scope === $bestScope && $discount > $bestDiscount)
            ) {
                $best = $promotion;
                $bestScope = $scope;
                $bestDiscount = $discount;
            }
        }

        return $best;
    }

    /** Конкретика привязки: 0 — товар, 1 — бренд, 2 — каталог; null — акция не про этот товар. */
    private static function scopeOf(array $promotion, string $productType, int $productId, ?int $brandId): ?int
    {
        $promotableType = $promotion['promotable_type'] ?? null;

        if ($promotableType === null) {
            return 2;
        }

        $promotableId = (int) ($promotion['promotable_id'] ?? 0);

        return match ($promotableType) {
            $productType => $promotableId === $productId ? 0 : null,
            'brand' => $brandId !== null && $promotableId === $brandId ? 1 : null,
            default => null,
        };
    }

    /** @param  array<string, mixed>  $promotion */
    private static function isActive(array $promotion, CarbonInterface $now): bool
    {
        return $now->betweenIncluded($promotion['starts_at'], $promotion['ends_at']);
    }
}
