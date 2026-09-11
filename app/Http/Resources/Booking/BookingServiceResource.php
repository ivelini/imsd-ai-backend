<?php

namespace App\Http\Resources\Booking;

use App\Models\Booking\BookingService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Услуга записи — компакт (для вложения в BookingItemResource).
 *
 * @mixin BookingService
 */
final class BookingServiceResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
        ];
    }
}
