<?php

namespace App\Http\Resources\Geo;

use App\Models\Delivery\City;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Город в справочнике для дропдаунов публичного API: {label, value, slug, region}.
 *
 * @mixin City
 */
final class CityReferenceResource extends JsonResource
{
    /** @var City */
    public $resource;

    /** @return array{label: string, value: int, slug: string|null, region: array{id: int, name: string}} */
    public function toArray(Request $request): array
    {
        return [
            'label' => $this->resource->name,
            'value' => $this->resource->id,
            'slug' => $this->resource->slug,
            'region' => $this->whenLoaded('region', fn () => new RegionReferenceResource($this->resource->region)),
        ];
    }
}
