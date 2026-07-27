<?php

namespace App\Models\Delivery;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Точка выдачи — физический адрес, где клиент забирает товар. */
class DeliveryPoint extends Model
{
    protected $fillable = [
        'city_id',
        'address',
        'phone',
        'email',
        'work_hours',
        'info',
        'pickup_from_truck',
    ];

    protected function casts(): array
    {
        return [
            'pickup_from_truck' => 'boolean',
        ];
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
