<?php

namespace App\Http\Resources\Catalog;

use App\DTOs\Catalog\Tire\TireFilterValues;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Ресурс фасетных значений фильтра каталога шин. */
final class TireFilterValuesResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var TireFilterValues $data */
        $data = $this->resource;

        return [
            'width' => $data->width,
            'profile' => $data->profile,
            'diameter' => $data->diameter,
            'season' => $data->season,
            'studded' => $data->studded,
            'brand' => $data->brand,
            'country' => $data->country,
            'delivery' => $data->delivery,
            'price' => $data->price,
        ];
    }
}
