<?php

namespace App\Http\Resources\Admin\Catalog\Warehouse;

use App\DTOs\Catalog\GetWarehouseStockResult;
use App\DTOs\Catalog\WarehouseStockRow;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Ресурс остатков товара на складах. */
final class WarehouseStockResource extends JsonResource
{
    /** @var GetWarehouseStockResult */
    public $resource;

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'data' => array_map(fn (WarehouseStockRow $row) => [
                'warehouse_id' => $row->warehouseId,
                'warehouse_name' => $row->warehouseName,
                'quantity' => $row->quantity,
                'purchase_price' => $row->purchasePrice,
                'final_price' => $row->finalPrice,
                'delivery_days' => $row->deliveryDays,
                'delivery_cost' => $row->deliveryCost,
            ], $this->resource->rows),
        ];
    }
}
