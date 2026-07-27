<?php

namespace App\Models\Delivery;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Время доставки из Челябинска в город клиента. */
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
