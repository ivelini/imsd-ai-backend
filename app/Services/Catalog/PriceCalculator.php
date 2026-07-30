<?php

namespace App\Services\Catalog;

use App\Models\Catalog\MarkupRule\WarehouseMarkupRule;

/** Расчёт финальной цены товара: purchase_price × коэффициент наценки склада. */
final readonly class PriceCalculator
{
    /**
     * @return float purchase_price, если правило не найдено; иначе price × coefficient (округлено до 2 знаков)
     */
    public function calculateFinalPrice(float $purchasePrice, int $warehouseId): float
    {
        $rule = WarehouseMarkupRule::where('warehouse_id', $warehouseId)
            ->where('price_from', '<=', $purchasePrice)
            ->where('price_to', '>=', $purchasePrice)
            ->orderBy('price_from')
            ->orderBy('price_to')
            ->first();

        if ($rule === null) {
            return $purchasePrice;
        }

        return round($purchasePrice * $rule->coefficient, 2);
    }
}
