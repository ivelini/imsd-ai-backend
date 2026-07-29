<?php

namespace App\Http\Resources\Admin\Geo;

use App\Models\Delivery\City;
use App\Models\Delivery\CityPriceRule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Ресурс правила наценки города. */
final class CityPriceRuleResource extends JsonResource
{
    /** @var CityPriceRule */
    public $resource;

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $rule = $this->resource;

        return [
            'id' => $rule->id,
            'city_id' => $rule->city_id,
            'city' => $this->whenLoaded('city', function () use ($rule) {
                /** @var City $city */
                $city = $rule->city;

                return ['id' => $city->id, 'name' => $city->name];
            }),
            'price_from' => (float) $rule->price_from,
            'price_to' => (float) $rule->price_to,
            'markup' => (float) $rule->markup,
            'created_at' => $rule->created_at->toIso8601String(),
        ];
    }
}
