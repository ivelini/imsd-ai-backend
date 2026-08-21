<?php

namespace App\Http\Resources\Catalog;

use App\Models\Catalog\Tire\TireProduct;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Элемент публичного списка шин (цена и сроки — из catalog_prices города). */
final class TireListItemResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var TireProduct $tire */
        $tire = $this->resource;

        return [
            'id' => $tire->id,
            'name' => $tire->name,
            'slug' => $tire->slug,
            'brand' => $this->whenLoaded('brand', fn () => new BrandResource($tire->brand)),
            'model' => $this->whenLoaded('model', fn () => new ProductModelReferenceResource($tire->model)),
            'width' => $tire->width,
            'profile' => $tire->profile,
            'diameter' => $tire->diameter,
            // Формат фасета: label — русское название (аксессор), value — значение из БД
            'season' => $tire->season !== null
                ? ['label' => $tire->season_label, 'value' => $tire->season->value]
                : null,
            'is_studded' => $tire->is_studded,
            'euro_label' => $tire->euro_label,
            'price' => $tire->city_price,
            'delivery_min' => $tire->city_delivery_min,
            'delivery_max' => $tire->city_delivery_max,
            'images' => $this->whenLoaded('images', fn () => ImageResource::collection($tire->images)),
        ];
    }
}
