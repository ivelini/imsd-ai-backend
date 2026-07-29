<?php

namespace App\DTOs\Catalog;

/** Входные данные для Action GetWarehouseStock. */
final readonly class GetWarehouseStockInput
{
    public function __construct(
        public string $productType,
        public int $productId,
        public int $cityId,
    ) {}
}
