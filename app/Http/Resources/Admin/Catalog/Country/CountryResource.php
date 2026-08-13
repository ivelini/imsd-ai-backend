<?php

namespace App\Http\Resources\Admin\Catalog\Country;

use App\Models\Catalog\Country\Country;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Страна производства.
 *
 * @mixin Country
 */
final class CountryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
        ];
    }
}
