<?php

namespace App\DTOs\TireImport;

/** Входные данные для UpsertStock. */
final readonly class UpsertStockInput
{
    public function __construct(
        public string $stockableType,
        public int $stockableId,
        public string $warehouseName,
        public ?int $quantity,
        public ?float $purchasePrice,
    ) {}
}
