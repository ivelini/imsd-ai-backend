<?php

namespace App\Services\Delivery;

use App\Models\Delivery\DeliverySchedule;
use Illuminate\Support\Facades\DB;

/**
 * Блок delivery листинга: выбор склада по минимальной цене города (quantity >= minQuantity)
 * и день недели ближайшей отгрузки. Чистые алгоритмы — DeliveryStockPicker и DeliveryInfoService::nextShipment.
 */
final readonly class DeliveryStockSelector
{
    public function __construct(
        private int $minQuantity,
    ) {}

    /**
     * @param  list<int>  $productIds
     * @return array<int, array{delivery_min: int|null, delivery_max: int|null, day_of_week: int|null}>
     *                                                                                                  id товара → блок delivery (null'ы — нет расписания/диапазона)
     */
    public function deliveryByProduct(array $productIds, int $cityId, string $morphClass): array
    {
        if ($productIds === []) {
            return [];
        }

        $rows = DB::table('stocks')
            ->join('catalog_prices', 'catalog_prices.stock_id', '=', 'stocks.id')
            ->where('stocks.stockable_type', $morphClass)
            ->whereIn('stocks.stockable_id', $productIds)
            ->where('stocks.quantity', '>=', $this->minQuantity)
            ->where('catalog_prices.city_id', $cityId)
            ->whereNotNull('catalog_prices.price')
            ->get([
                'stocks.id as stock_id',
                'stocks.stockable_id',
                'stocks.warehouse_id',
                'stocks.quantity',
                'catalog_prices.price',
                'catalog_prices.delivery_min',
                'catalog_prices.delivery_max',
            ]);

        $selected = [];
        $warehouseIds = [];

        foreach ($rows->groupBy('stockable_id') as $productId => $productRows) {
            $pick = DeliveryStockPicker::pick(
                $productRows
                    ->map(fn (object $row): array => [
                        'stock_id' => (int) $row->stock_id,
                        'stockable_id' => (int) $row->stockable_id,
                        'warehouse_id' => (int) $row->warehouse_id,
                        'quantity' => (int) $row->quantity,
                        'price' => $row->price !== null ? (float) $row->price : null,
                        'delivery_min' => $row->delivery_min !== null ? (int) $row->delivery_min : null,
                        'delivery_max' => $row->delivery_max !== null ? (int) $row->delivery_max : null,
                    ])
                    ->all(),
                $this->minQuantity,
            );

            if ($pick !== null) {
                $selected[$productId] = $pick;
                $warehouseIds[$pick['warehouse_id']] = true;
            }
        }

        $schedules = DeliverySchedule::whereIn('warehouse_id', array_keys($warehouseIds))
            ->get()
            ->groupBy('warehouse_id');

        $result = [];
        foreach ($selected as $productId => $pick) {
            $next = DeliveryInfoService::nextShipment($schedules->get($pick['warehouse_id']));

            $result[$productId] = [
                'delivery_min' => $pick['delivery_min'],
                'delivery_max' => $pick['delivery_max'],
                'day_of_week' => $next['day_of_week'] ?? null,
            ];
        }

        return $result;
    }
}
