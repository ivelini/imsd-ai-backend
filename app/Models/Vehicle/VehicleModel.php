<?php

namespace App\Models\Vehicle;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Модель автомобиля (3 Series, X5…). */
class VehicleModel extends Model
{
    protected $fillable = [
        'make_id',
        'name',
        'generation',
    ];

    public function make(): BelongsTo
    {
        return $this->belongsTo(VehicleMake::class, 'make_id');
    }

    public function modifications(): HasMany
    {
        return $this->hasMany(VehicleModification::class, 'model_id');
    }
}
