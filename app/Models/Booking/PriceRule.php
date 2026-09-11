<?php

namespace App\Models\Booking;

use App\Enums\Booking\CarType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Прайс-правило: цена услуги за единицу (1 колесо/шт) для комбинации (услуга, радиус, тип).
 *
 * @property int $id
 * @property int $service_id
 * @property int $radius
 * @property CarType $car_type
 * @property int $price
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class PriceRule extends Model
{
    protected $table = 'booking_price_rules';

    protected $fillable = [
        'service_id',
        'radius',
        'car_type',
        'price',
    ];

    protected function casts(): array
    {
        return [
            'car_type' => CarType::class,
        ];
    }

    /** @return BelongsTo<BookingService, $this> */
    public function service(): BelongsTo
    {
        return $this->belongsTo(BookingService::class, 'service_id');
    }
}
