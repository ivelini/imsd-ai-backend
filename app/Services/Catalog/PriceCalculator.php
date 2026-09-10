<?php

namespace App\Services\Catalog;

use App\Models\Catalog\MarkupRule\WarehouseMarkupRule;

/** Расчёт цены продажи товара: purchase_price × коэффициент наценки склада. */
final readonly class PriceCalculator
{
    /**
     * Цена продажи одного остатка по правилу наценки склада (ADM-4.2.3).
     * Единый путь для импорта и админки: поиск правила + применение.
     */
    public function calculateForWarehouse(float $purchasePrice, int $warehouseId): float
    {
        return $this->applyRule($purchasePrice, $this->findRule($purchasePrice, $warehouseId));
    }

    /** Правило наценки склада, покрывающее цену (единичные вызовы — импорт). */
    public function findRule(float $purchasePrice, int $warehouseId): ?array
    {
        $rules = WarehouseMarkupRule::where('warehouse_id', $warehouseId)
            ->get()
            ->map(fn (WarehouseMarkupRule $rule) => $rule->only(['price_from', 'price_to', 'coefficient']))
            ->all();

        return MarkupRuleMatcher::match($purchasePrice, $rules);
    }

    /** Цена продажи по правилу; без правила — закупочная цена. */
    public function applyRule(float $purchasePrice, ?array $rule): float
    {
        return $rule === null
            ? $purchasePrice
            : round($purchasePrice * $rule['coefficient'], 2);
    }
}
