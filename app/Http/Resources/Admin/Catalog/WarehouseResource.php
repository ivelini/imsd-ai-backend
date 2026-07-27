<?php

namespace App\Http\Resources\Admin\Catalog;

use App\Models\Catalog\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Ресурс склада. */
final class WarehouseResource extends JsonResource
{
    /** @var Warehouse */
    public $resource;

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'created_at' => $this->resource->created_at->toIso8601String(),
        ];
    }
}
