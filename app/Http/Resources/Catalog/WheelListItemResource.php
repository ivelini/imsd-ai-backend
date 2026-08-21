<?php

namespace App\Http\Resources\Catalog;

use App\Models\Catalog\Wheel\WheelProduct;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Элемент публичного списка дисков (цена и сроки — из catalog_prices города). */
final class WheelListItemResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var WheelProduct $wheel */
        $wheel = $this->resource;

        return [
            'id' => $wheel->id,
            'name' => $wheel->name,
            'slug' => $wheel->slug,
            'brand' => $this->whenLoaded('brand', fn () => new BrandResource($wheel->brand)),
            'model' => $this->whenLoaded('model', fn () => new ProductModelReferenceResource($wheel->model)),
            'width' => $wheel->width,
            'diameter' => $wheel->diameter,
            'pcd' => $wheel->pcd,
            'et' => $wheel->et,
            'hub_diameter' => $wheel->hub_diameter,
            // Формат фасета: label — русское название (WheelType::label), value — значение из БД
            'type' => $wheel->type !== null
                ? ['label' => $wheel->type->label(), 'value' => $wheel->type->value]
                : null,
            'color' => $wheel->color,
            'price' => $wheel->city_price,
            'delivery_min' => $wheel->city_delivery_min,
            'delivery_max' => $wheel->city_delivery_max,
            'images' => $this->whenLoaded('images', fn () => ImageResource::collection($wheel->images)),
        ];
    }
}
