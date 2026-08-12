<?php

namespace App\Models\Delivery;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Время доставки из Челябинска в город клиента.
 *
 * @property int $id
 * @property int $city_id
 * @property int $delivery_days
 * @property int $priority
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read City $city
 */
class CityDeliveryTime extends Model
{
    protected $fillable = [
        'city_id',
        'delivery_days',
        'priority',
    ];

    protected function casts(): array
    {
        return [
            'delivery_days' => 'integer',
            'priority' => 'integer',
        ];
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
