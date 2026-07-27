<?php

namespace App\Models\Delivery;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Правило наценки по городу: диапазон цены → фиксированная наценка. */
class CityPriceRule extends Model
{
    protected $fillable = [
        'city_id',
        'price_from',
        'price_to',
        'markup',
    ];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
