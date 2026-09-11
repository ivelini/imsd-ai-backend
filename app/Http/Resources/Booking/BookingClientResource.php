<?php

namespace App\Http\Resources\Booking;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Клиент записи — компакт (для вложения в BookingResource).
 *
 * @mixin User
 */
final class BookingClientResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'phone' => $this->resource->phone,
        ];
    }
}
