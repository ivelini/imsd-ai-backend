<?php

namespace App\Http\Resources\Admin\Catalog\Brand;

use App\Models\Catalog\Brand\Brand;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ресурс бренда.
 *
 * @mixin Brand
 */
final class BrandResource extends JsonResource
{
    /** @var Brand */
    public $resource;

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'slug' => $this->resource->slug,
            'logo' => $this->resource->logo,
            'description' => $this->resource->description,
            'type' => $this->resource->type,
            'products_count' => $this->whenCounted('tireProducts', fn () => ($this->resource->tire_products_count ?? 0) + ($this->resource->wheel_products_count ?? 0)),
            'created_at' => $this->resource->created_at->toIso8601String(),
        ];
    }
}
