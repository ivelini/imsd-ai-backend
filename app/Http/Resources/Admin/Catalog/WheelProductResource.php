<?php

namespace App\Http\Resources\Admin\Catalog;

use App\Models\Catalog\Brand;
use App\Models\Catalog\WheelProduct;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Ресурс диска. */
final class WheelProductResource extends JsonResource
{
    /** @var WheelProduct */
    public $resource;

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $wheel = $this->resource;

        return [
            'id' => $wheel->id,
            'brand_id' => $wheel->brand_id,
            'brand' => $this->whenLoaded('brand', function () use ($wheel) {
                /** @var Brand $brand */
                $brand = $wheel->brand;

                return ['id' => $brand->id, 'name' => $brand->name];
            }),
            'name' => $wheel->name,
            'supplier_id' => $wheel->supplier_id,
            'country_id' => $wheel->country_id,
            'ean' => $wheel->ean,
            'type' => $wheel->type?->value,
            'color' => $wheel->color,
            'pcd' => $wheel->pcd,
            'et' => $wheel->et,
            'hub_diameter' => $wheel->hub_diameter,
            'width' => $wheel->width,
            'diameter' => $wheel->diameter,
            'description' => $wheel->description,
            'is_published' => $wheel->is_published,
            'is_bestseller' => $wheel->is_bestseller,
            'is_new' => $wheel->is_new,
            'created_at' => $wheel->created_at->toIso8601String(),
        ];
    }
}
