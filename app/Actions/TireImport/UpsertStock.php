<?php

namespace App\Actions\TireImport;

use App\DTOs\TireImport\UpsertStockInput;
use App\Models\Catalog\Stock;
use App\Models\Catalog\WarehouseMarkupRule;
use App\Services\TireImport\ReferenceResolver;

/** Создание или обновление остатка на складе для товара (шина/диск). */
final readonly class UpsertStock
{
    public function __construct(
        private ReferenceResolver $referenceResolver,
    ) {}

    public function execute(UpsertStockInput $input): void
    {
        $warehouse = $this->referenceResolver->resolveWarehouse($input->warehouseName);

        $price = null;
        if ($input->purchasePrice !== null) {
            $rule = WarehouseMarkupRule::where('warehouse_id', $warehouse->id)
                ->where('price_from', '<=', $input->purchasePrice)
                ->where('price_to', '>=', $input->purchasePrice)
                ->orderBy('price_from')
                ->orderBy('price_to')
                ->first();

            $price = $rule
                ? round($input->purchasePrice * $rule->coefficient, 2)
                : $input->purchasePrice;
        }

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
