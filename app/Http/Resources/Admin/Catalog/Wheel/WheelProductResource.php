<?php

namespace App\Http\Resources\Admin\Catalog\Wheel;

use App\Http\Resources\Admin\Catalog\Brand\BrandBriefResource;
use App\Http\Resources\Admin\Catalog\Image\ImageResource;
use App\Http\Resources\Admin\Catalog\Model\ProductModelBriefResource;
use App\Http\Resources\Admin\Catalog\Warehouse\StockResource;
use App\Models\Catalog\Wheel\WheelProduct;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ресурс диска — только форматирование, без логики.
 *
 * @mixin WheelProduct
 */
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
            'brand' => $this->whenLoaded('brand', fn () => new BrandBriefResource($wheel->brand)),
            'model' => $this->whenLoaded('model', fn () => new ProductModelBriefResource($wheel->model)),
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
            'stocks' => $this->whenLoaded('stocks', fn () => StockResource::collection($wheel->stocks)),
            'delivery' => $this->whenLoaded('delivery'),
            'images' => $this->whenLoaded('images', fn () => ImageResource::collection($wheel->images)),
            'created_at' => $wheel->created_at->toIso8601String(),
        ];
    }
}
