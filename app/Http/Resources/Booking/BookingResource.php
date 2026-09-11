<?php

namespace App\Http\Resources\Booking;

use App\Models\Booking\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Запись на шиномонтаж для публичного API: снимок параметров и цены, клиент, состав.
 *
 * @mixin Booking
 */
final class BookingResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'date' => $this->resource->slot?->date?->format('Y-m-d'),
            'start_time' => $this->resource->start_time,
            'status' => $this->resource->status,
            'source' => $this->resource->source,
            'radius' => $this->resource->radius,
            'car_type' => $this->resource->car_type,
            'plate' => $this->resource->plate,
            'total_price' => $this->resource->total_price->toKopecks(),
            'user' => $this->whenLoaded('user', fn () => new BookingClientResource($this->resource->user)),
            'items' => $this->whenLoaded('items', fn () => BookingItemResource::collection($this->resource->items)),
        ];
    }
}
