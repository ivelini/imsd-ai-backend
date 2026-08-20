<?php

namespace App\Http\Resources\Geo;

use App\Models\Delivery\Region;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Регион города в справочнике публичного API: {id, name}. */
final class RegionReferenceResource extends JsonResource
{
    /** @var Region */
    public $resource;

    /** @return array{id: int, name: string} */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
        ];
    }
}
