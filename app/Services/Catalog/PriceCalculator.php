<?php

namespace App\Services\Catalog;

use App\Models\Catalog\MarkupRule\WarehouseMarkupRule;
use Illuminate\Support\Collection;

/** Расчёт финальной цены товара: purchase_price × коэффициент наценки склада. */
final readonly class PriceCalculator
{
    /** Правило наценки для цены и склада (SQL, единичные вызовы — импорт). */
    public function findRule(float $purchasePrice, int $warehouseId): ?WarehouseMarkupRule
    {
        return WarehouseMarkupRule::query()
            ->where('warehouse_id', $warehouseId)
            ->where('price_from', '<=', $purchasePrice)
            ->where('price_to', '>=', $purchasePrice)
            ->orderBy('price_from')
            ->orderBy('price_to')
            ->first();
    }

    /** Финальная цена по правилу; без правила — закупочная цена. */
    public function applyRule(float $purchasePrice, ?WarehouseMarkupRule $rule): float
    {
        return $rule === null
            ? $purchasePrice
            : round($purchasePrice * $rule->coefficient, 2);
    }

    /**
     * Массовый пересчёт: поиск правила в предзагруженной коллекции (без БД).
     *
     * @param  Collection<array-key, Collection<int, array<string, int|float>>>  $allRules  правила, сгруппированные по warehouse_id
     */
    public function calculateFinalPrice(float $purchasePrice, int $warehouseId, Collection $allRules): float
    {
        $rule = $allRules->get($warehouseId, collect())
            ->sortBy([['price_from', 'asc'], ['price_to', 'asc']])
            ->first(
                fn (array $r) => $purchasePrice >= $r['price_from'] && $purchasePrice <= $r['price_to']
            );

        return $rule === null
            ? $purchasePrice
            : round($purchasePrice * $rule['coefficient'], 2);
    }
}
