<?php

namespace App\Models\Delivery;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Регион (субъект РФ). */
class Region extends Model
{
    protected $fillable = [
        'code',
        'name',
    ];

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }
}
