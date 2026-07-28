<?php

namespace App\Http\Resources\Admin\Catalog;

use Illuminate\Http\Resources\Json\JsonResource;

/** Страна производства. */
final class CountryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->resource->id,
            'code' => $this->resource->code,
            'name' => $this->resource->name,
        ];
    }
}
