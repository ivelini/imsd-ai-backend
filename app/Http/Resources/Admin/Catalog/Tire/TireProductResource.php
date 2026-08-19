<?php

namespace App\Http\Resources\Admin\Catalog\Tire;

use App\Http\Resources\Admin\Catalog\Brand\BrandBriefResource;
use App\Http\Resources\Admin\Catalog\Image\ImageResource;
use App\Http\Resources\Admin\Catalog\Model\ProductModelBriefResource;
use App\Http\Resources\Admin\Catalog\Warehouse\StockResource;
use App\Models\Catalog\Tire\TireProduct;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ресурс шины — только форматирование, без логики.
 *
 * @mixin TireProduct
 */
final class TireProductResource extends JsonResource
{
    /** @var TireProduct */
    public $resource;

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $tire = $this->resource;

        return [
            'id' => $tire->id,
            'brand_id' => $tire->brand_id,
            'brand' => $this->whenLoaded('brand', fn () => new BrandBriefResource($tire->brand)),
            'model' => $this->whenLoaded('model', fn () => new ProductModelBriefResource($tire->model)),
            'name' => $tire->name,
            'supplier_id' => $tire->supplier_id,
            'country_id' => $tire->country_id,
            'ean' => $tire->ean,
            'season' => $tire->season?->value,
            'width' => $tire->width,
            'profile' => $tire->profile,
            'diameter' => $tire->diameter,
            'load_index' => $tire->load_index,
            'speed_index' => $tire->speed_index,
            'is_studded' => $tire->is_studded,
            'is_runflat' => $tire->is_runflat,
            'is_xl' => $tire->is_xl,
            'year' => $tire->year,
            'description' => $tire->description,
            'is_published' => $tire->is_published,
            'is_bestseller' => $tire->is_bestseller,
            'is_new' => $tire->is_new,
            'stocks' => $this->whenLoaded('stocks', fn () => StockResource::collection($tire->stocks)),
            'delivery' => $this->whenLoaded('delivery'),
            'images' => $this->whenLoaded('images', fn () => ImageResource::collection($tire->images)),
            'created_at' => $tire->created_at->toIso8601String(),
        ];
    }
}
