<?php

namespace App\Actions\Warehouse;

use App\DTOs\Catalog\GetWarehouseStockInput;
use App\DTOs\Catalog\GetWarehouseStockResult;
use App\DTOs\Catalog\WarehouseStockRow;
use App\Models\Catalog\Warehouse\Stock;
use App\Models\Catalog\Warehouse\Warehouse;
use App\Models\Delivery\CatalogPrice;
use App\Services\Catalog\DeliveryCostCalculator;
use App\Services\Catalog\DeliveryTimeCalculator;

/** Получить остатки товара на всех складах с ценами и доставкой до города. */
final readonly class GetWarehouseStock
{
    public function __construct(
        private DeliveryTimeCalculator $deliveryTime,
        private DeliveryCostCalculator $deliveryCost,
    ) {}

    public function execute(GetWarehouseStockInput $input): GetWarehouseStockResult
    {
        $warehouses = Warehouse::orderBy('name')->get();

        $rows = [];
        foreach ($warehouses as $warehouse) {
            $stock = Stock::where('stockable_type', $input->productType)
                ->where('stockable_id', $input->productId)
                ->where('warehouse_id', $warehouse->id)
                ->first();

            $quantity = $stock !== null ? $stock->quantity : 0;
            $purchasePrice = $stock?->purchase_price;

            $catalogPrice = $stock
                ? CatalogPrice::where('stock_id', $stock->id)
                    ->where('city_id', $input->cityId)
                    ->first()
                : null;
            $finalPrice = $catalogPrice?->price;

            $deliveryDays = $this->deliveryTime->calculate($warehouse->id, $input->cityId);
            $deliveryCostValue = $this->deliveryCost->calculate($input->cityId, $finalPrice);

            $rows[] = new WarehouseStockRow(
                warehouseId: $warehouse->id,
                warehouseName: $warehouse->name,
                quantity: $quantity,
                purchasePrice: $purchasePrice,
                finalPrice: $finalPrice,
                deliveryDays: $deliveryDays,
                deliveryCost: $deliveryCostValue,
            );
        }

        return new GetWarehouseStockResult($rows);
    }
}
