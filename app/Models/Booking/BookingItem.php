<?php

namespace App\Models\Booking;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Строка состава записи с ценой на момент записи (снимок, корректируется оператором — ФТ-19 tireslot).
 *
 * @property int $id
 * @property int $booking_id
 * @property int $service_id
 * @property int $price цена за единицу (снимок)
 * @property int $quantity 1–4
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class BookingItem extends Model
{
    protected $table = 'booking_items';

    protected $fillable = [
        'booking_id',
        'service_id',
        'price',
        'quantity',
    ];

    /** @return BelongsTo<Booking, $this> */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /** @return BelongsTo<BookingService, $this> */
    public function service(): BelongsTo
    {
        return $this->belongsTo(BookingService::class, 'service_id');
    }
}
