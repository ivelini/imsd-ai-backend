<?php

namespace App\Http\Resources\Admin\Catalog\Warehouse;

use App\DTOs\Catalog\WarehouseStockRow;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Строка остатков товара на складе.
 *
 * @property WarehouseStockRow $resource
 */
final class WarehouseStockRowResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'warehouse_id' => $this->resource->warehouseId,
            'warehouse_name' => $this->resource->warehouseName,
            'quantity' => $this->resource->quantity,
            'purchase_price' => $this->resource->purchasePrice,
            'final_price' => $this->resource->finalPrice,
            'delivery_days' => $this->resource->deliveryDays,
            'delivery_cost' => $this->resource->deliveryCost,
        ];
    }
}
