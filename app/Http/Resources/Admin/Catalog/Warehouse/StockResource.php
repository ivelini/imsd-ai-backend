<?php

namespace App\Http\Resources\Admin\Catalog\Warehouse;

use App\Models\Catalog\Warehouse\Stock;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Сток товара во вложенной структуре (шина, диск). */
final class StockResource extends JsonResource
{
    /** @var Stock */
    public $resource;

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'warehouse_id' => $this->resource->warehouse_id,
            'warehouse' => $this->resource->warehouse->name,
            'quantity' => $this->resource->quantity,
            'purchase_price' => $this->resource->purchase_price,
            'price' => $this->resource->price,
            'delivery_days' => $this->resource->deliveryDays,
        ];
    }
}
