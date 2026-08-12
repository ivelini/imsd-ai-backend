<?php

namespace App\Http\Resources\Admin\Geo;

use App\Models\Delivery\City;
use App\Models\Delivery\Region;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ресурс города.
 *
 * @mixin City
 */
final class CityResource extends JsonResource
{
    /** @var City */
    public $resource;

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'region' => $this->whenLoaded('region', function () {
                /** @var Region $region */
                $region = $this->resource->region;

                return $region->name;
            }),
        ];
    }
}
