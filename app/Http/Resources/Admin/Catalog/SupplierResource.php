<?php

namespace App\Http\Resources\Admin\Catalog;

use App\Models\Catalog\Supplier;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Ресурс поставщика. */
final class SupplierResource extends JsonResource
{
    /** @var Supplier */
    public $resource;

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'code' => $this->resource->code,
            'created_at' => $this->resource->created_at->toIso8601String(),
        ];
    }
}
