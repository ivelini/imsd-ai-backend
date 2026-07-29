<?php

namespace App\Actions\TireImport;

use App\DTOs\TireImport\UpsertStockInput;
use App\Models\Catalog\Stock;
use App\Services\Catalog\PriceCalculator;
use App\Services\TireImport\ReferenceResolver;

/** Создание или обновление остатка на складе для товара (шина/диск). */
final readonly class UpsertStock
{
    public function __construct(
        private ReferenceResolver $referenceResolver,
        private PriceCalculator $priceCalculator,
    ) {}

    public function execute(UpsertStockInput $input): void
    {
        $warehouse = $this->referenceResolver->resolveWarehouse($input->warehouseName);

        $price = $input->purchasePrice !== null
            ? $this->priceCalculator->calculateFinalPrice($input->purchasePrice, $warehouse->id)
            : null;

        Stock::updateOrCreate(
            [
                'stockable_type' => $input->stockableType,
                'stockable_id' => $input->stockableId,
                'warehouse_id' => $warehouse->id,
            ],
            [
                'quantity' => $input->quantity ?? 0,
                'purchase_price' => $input->purchasePrice,
                'price' => $price,
            ],
        );
    }
}
