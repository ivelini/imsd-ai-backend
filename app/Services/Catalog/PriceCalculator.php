<?php

namespace App\Services\Catalog;

use App\Models\Catalog\MarkupRule\WarehouseMarkupRule;
use Illuminate\Support\Collection;

/** Расчёт финальной цены товара: purchase_price × коэффициент наценки склада. */
final readonly class PriceCalculator
{
    /** Правило наценки склада, покрывающее цену (единичные вызовы — импорт). */
    public function findRule(float $purchasePrice, int $warehouseId): ?array
    {
        $rules = WarehouseMarkupRule::where('warehouse_id', $warehouseId)
            ->get()
            ->map(fn (WarehouseMarkupRule $rule) => $rule->only(['price_from', 'price_to', 'coefficient']))
            ->all();

        return MarkupRuleMatcher::match($purchasePrice, $rules);
    }

    /** Финальная цена по правилу; без правила — закупочная цена. */
    public function applyRule(float $purchasePrice, ?array $rule): float
    {
        return $rule === null
            ? $purchasePrice
            : round($purchasePrice * $rule['coefficient'], 2);
    }

    /**
     * Массовый пересчёт: поиск правила в предзагруженной коллекции (без БД).
     *
     * @param  Collection<array-key, Collection<int, array<string, int|float>>>  $allRules  правила, сгруппированные по warehouse_id
     */
    public function calculateFinalPrice(float $purchasePrice, int $warehouseId, Collection $allRules): float
    {
        $rule = MarkupRuleMatcher::match($purchasePrice, $allRules->get($warehouseId, collect())->all());

        return $this->applyRule($purchasePrice, $rule);
    }
}
