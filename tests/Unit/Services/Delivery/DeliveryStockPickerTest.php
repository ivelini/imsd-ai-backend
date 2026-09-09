<?php

namespace Tests\Unit\Services\Delivery;

use App\Services\Delivery\DeliveryStockPicker;
use PHPUnit\Framework\TestCase;

/** Выбор склада для блока delivery: минимальная цена города, quantity >= minQuantity. */
class DeliveryStockPickerTest extends TestCase
{
    public function test_pick_cheapest_stock_with_min_quantity(): void
    {
        $rows = [
            $this->row(1, 100, 10),
            $this->row(2, 90, 2),
            $this->row(3, null, 20),
            $this->row(4, 95, 4),
        ];

        $picked = DeliveryStockPicker::pick($rows, 4);

        $this->assertSame(4, $picked['stock_id']);
    }

    public function test_pick_uses_lowest_id_on_price_tie(): void
    {
        $rows = [
            $this->row(7, 100, 4),
            $this->row(3, 100, 9),
        ];

        $picked = DeliveryStockPicker::pick($rows, 4);

        $this->assertSame(3, $picked['stock_id']);
    }

    public function test_pick_returns_null_without_min_quantity(): void
    {
        $rows = [$this->row(1, 100, 3)];

        $this->assertNull(DeliveryStockPicker::pick($rows, 4));
    }

    public function test_pick_returns_null_on_empty(): void
    {
        $this->assertNull(DeliveryStockPicker::pick([], 4));
    }

    /** @return array{stock_id: int, stockable_id: int, warehouse_id: int, quantity: int, price: float|null, delivery_min: int|null, delivery_max: int|null} */
    private function row(int $stockId, ?float $price, int $quantity): array
    {
        return [
            'stock_id' => $stockId,
            'stockable_id' => 10,
            'warehouse_id' => $stockId,
            'quantity' => $quantity,
            'price' => $price,
            'delivery_min' => 2,
            'delivery_max' => 5,
        ];
    }
}
