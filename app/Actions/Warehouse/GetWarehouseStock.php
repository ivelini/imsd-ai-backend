<?php

namespace App\Actions\Warehouse;

use App\DTOs\Catalog\GetWarehouseStockInput;
use App\DTOs\Catalog\GetWarehouseStockResult;
use App\DTOs\Catalog\WarehouseStockRow;
use App\Models\Catalog\Warehouse\Stock;
use App\Models\Catalog\Warehouse\Warehouse;
use App\Models\Delivery\CatalogPrice;
use App\Models\Delivery\DeliverySchedule;
use App\Services\Catalog\DeliveryTimeCalculator;

/** Получить остатки товара на всех складах с ценами и доставкой до города. */
final readonly class GetWarehouseStock
{
    public function __construct(
        private DeliveryTimeCalculator $deliveryTime,
    ) {}

    public function execute(GetWarehouseStockInput $input): GetWarehouseStockResult
    {
        $warehouses = Warehouse::orderBy('name')->get();
        $warehouseIds = $warehouses->pluck('id')->all();

        // Batch 1: все остатки для товара на всех складах
        $stocksByWarehouseId = Stock::where('stockable_type', $input->productType)
            ->where('stockable_id', $input->productId)
            ->whereIn('warehouse_id', $warehouseIds)
            ->get()
            ->keyBy('warehouse_id');

        // Batch 2: все каталоговые цены для этих остатков
        $stockIds = $stocksByWarehouseId->pluck('id')->filter()->all();
        $catalogPricesByStockId = $stockIds !== []
            ? CatalogPrice::whereIn('stock_id', $stockIds)
                ->where('city_id', $input->cityId)
                ->get()
                ->keyBy('stock_id')
            : collect();

        // Batch 3: графики отгрузки для всех складов
        $schedulesByWarehouseId = DeliverySchedule::whereIn('warehouse_id', $warehouseIds)
            ->get()
            ->groupBy('warehouse_id');

        // Batch 4: сроки доставки до города для всех складов
        $deliveryDaysByWarehouseId = $this->deliveryTime->calculateAll(
            $warehouseIds,
            $input->cityId,
            $schedulesByWarehouseId,
        );

        $rows = [];
        foreach ($warehouses as $warehouse) {
            $stock = $stocksByWarehouseId->get($warehouse->id);

            $quantity = $stock !== null ? $stock->quantity : 0;
            $purchasePrice = $stock?->purchase_price !== null
                ? (float) $stock->purchase_price
                : null;

            // final_price уже включает наценку города (catalog_prices пересчитан)
            $finalPrice = $stock !== null
                ? $catalogPricesByStockId->get($stock->id)?->price
                : null;

            $rows[] = new WarehouseStockRow(
                warehouseId: $warehouse->id,
                warehouseName: $warehouse->name,
                quantity: $quantity,
                purchasePrice: $purchasePrice,
                finalPrice: $finalPrice,
                deliveryDays: $deliveryDaysByWarehouseId[$warehouse->id] ?? null,
            );
        }

        return new GetWarehouseStockResult($rows);
    }
}
