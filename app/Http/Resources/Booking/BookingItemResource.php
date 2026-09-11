<?php

namespace App\Http\Resources\Booking;

use App\Models\Booking\BookingItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Строка состава записи (снимок цены на момент записи).
 *
 * @mixin BookingItem
 */
final class BookingItemResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'service' => $this->whenLoaded('service', fn () => new BookingServiceResource($this->resource->service)),
            'price' => $this->resource->price->toKopecks(),
            'quantity' => $this->resource->quantity,
        ];
    }
}
