<?php

namespace App\DTOs\Catalog;

/** Строка остатка товара на складе. */
final readonly class WarehouseStockRow
{
    public function __construct(
        public int $warehouseId,
        public string $warehouseName,
        public int $quantity,
        public ?float $purchasePrice,
        public ?float $finalPrice,
        public ?int $deliveryDays,
    ) {}
}
