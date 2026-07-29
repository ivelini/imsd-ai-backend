<?php

namespace App\DTOs\Catalog;

/** Результат Action GetWarehouseStock. */
final readonly class GetWarehouseStockResult
{
    /** @param WarehouseStockRow[] $rows */
    public function __construct(
        public array $rows,
    ) {}
}
