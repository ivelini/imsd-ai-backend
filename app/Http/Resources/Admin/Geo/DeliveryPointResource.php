<?php

namespace App\Http\Resources\Admin\Geo;

use App\Models\Delivery\City;
use App\Models\Delivery\DeliveryPoint;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ресурс точки выдачи.
 *
 * @mixin DeliveryPoint
 */
final class DeliveryPointResource extends JsonResource
{
    /** @var DeliveryPoint */
    public $resource;

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $point = $this->resource;

        return [
            'id' => $point->id,
            'city_id' => $point->city_id,
            'city' => $this->whenLoaded('city', function () use ($point) {
                /** @var City $city */
                $city = $point->city;

                return ['id' => $city->id, 'name' => $city->name];
            }),
            'address' => $point->address,
            'phone' => $point->phone,
            'email' => $point->email,
            'work_hours' => $point->work_hours,
            'info' => $point->info,
            'pickup_from_truck' => $point->pickup_from_truck,
            'created_at' => $point->created_at->toIso8601String(),
        ];
    }
}
