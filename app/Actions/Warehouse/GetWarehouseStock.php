<?php

namespace App\Actions\Warehouse;

use App\DTOs\Catalog\GetWarehouseStockInput;
use App\DTOs\Catalog\GetWarehouseStockResult;
use App\DTOs\Catalog\WarehouseStockRow;
use App\Models\Catalog\Warehouse\Stock;
use App\Models\Catalog\Warehouse\Warehouse;
use App\Models\Delivery\CatalogPrice;
use App\Models\Delivery\CityPriceRule;
use App\Models\Delivery\DeliverySchedule;
use App\Services\Catalog\DeliveryTimeCalculator;
use Illuminate\Support\Collection;

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

        // Batch 5: все правила стоимости доставки для города (in-memory фильтр)
        $priceRules = CityPriceRule::where('city_id', $input->cityId)->get();

        $rows = [];
        foreach ($warehouses as $warehouse) {
            $stock = $stocksByWarehouseId->get($warehouse->id);

            $quantity = $stock !== null ? $stock->quantity : 0;
            $purchasePrice = $stock?->purchase_price;

            $finalPrice = $stock !== null
                ? $catalogPricesByStockId->get($stock->id)?->price
                : null;

            $deliveryDays = $deliveryDaysByWarehouseId[$warehouse->id] ?? null;
            $deliveryCostValue = $this->calculateDeliveryCost($priceRules, $finalPrice);

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

    /** Ищет правило стоимости доставки в памяти без запросов к БД. */
    private function calculateDeliveryCost(Collection $rules, ?float $finalPrice): ?float
    {
        if ($finalPrice === null) {
            return null;
        }

        $rule = $rules->first(
            fn (CityPriceRule $r) => $r->price_from <= $finalPrice && $r->price_to >= $finalPrice,
        );

        return $rule?->markup;
    }
}
