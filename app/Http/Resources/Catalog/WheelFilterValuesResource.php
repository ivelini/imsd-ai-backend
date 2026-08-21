<?php

namespace App\Http\Resources\Catalog;

use App\DTOs\Catalog\Wheel\WheelFilterValues;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Ресурс фасетных значений фильтра каталога дисков. */
final class WheelFilterValuesResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var WheelFilterValues $data */
        $data = $this->resource;

        return [
            'width' => $data->width,
            'diameter' => $data->diameter,
            'pcd' => $data->pcd,
            'et' => $data->et,
            'hub_diameter' => $data->hub_diameter,
            'type' => $data->type,
            'color' => $data->color,
            'brand' => $data->brand,
            'country' => $data->country,
            'delivery' => $data->delivery,
            'price' => $data->price,
        ];
    }
}
