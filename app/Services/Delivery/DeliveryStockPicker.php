<?php

namespace App\Services\Delivery;

/** Выбор склада для блока delivery листинга: минимальная цена города среди стоков с quantity >= minQuantity. */
final class DeliveryStockPicker
{
    /**
     * @param  list<array{stock_id: int, stockable_id: int, warehouse_id: int, quantity: int, price: float|null, delivery_min: int|null, delivery_max: int|null}>  $rows
     * @return array{stock_id: int, warehouse_id: int, delivery_min: int|null, delivery_max: int|null}|null
     */
    public static function pick(array $rows, int $minQuantity): ?array
    {
        $candidates = array_filter(
            $rows,
            fn (array $row): bool => $row['price'] !== null && $row['quantity'] >= $minQuantity,
        );

        if ($candidates === []) {
            return null;
        }

        // Tiebreaker по stock_id — детерминизм выбора при равных ценах
        usort($candidates, fn (array $a, array $b): int => $a['price'] <=> $b['price'] ?: $a['stock_id'] <=> $b['stock_id']);

        $best = $candidates[0];

        return [
            'stock_id' => $best['stock_id'],
            'warehouse_id' => $best['warehouse_id'],
            'delivery_min' => $best['delivery_min'],
            'delivery_max' => $best['delivery_max'],
        ];
    }
}
