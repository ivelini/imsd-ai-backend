<?php

namespace App\Http\Resources\Catalog;

use App\DTOs\Catalog\OriginInfo;
use App\Models\Catalog\Origin\ProductOrigin;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Происхождение товара в публичном листинге.
 *
 * Каждое поле — OriginInfo {badge, description} (value object сериализуется сам,
 * как euro_label); null, если колонка не заполнена.
 */
final class ProductOriginResource extends JsonResource
{
    /** @var ProductOrigin */
    public $resource;

    /** @return array{vendor: OriginInfo|null, manufacture_country: OriginInfo|null, manufacture_year: OriginInfo|null} */
    public function toArray(Request $request): array
    {
        return [
            'vendor' => $this->resource->vendor,
            'manufacture_country' => $this->resource->manufacture_country,
            'manufacture_year' => $this->resource->manufacture_year,
        ];
    }
}
