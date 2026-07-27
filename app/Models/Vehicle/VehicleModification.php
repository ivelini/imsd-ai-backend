<?php

namespace App\Models\Vehicle;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Модификация автомобиля (двигатель + комплектация). */
class VehicleModification extends Model
{
    protected $fillable = [
        'model_id',
        'name',
    ];

    public function model(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class, 'model_id');
    }

    public function tireSizes(): HasMany
    {
        return $this->hasMany(VehicleTireSize::class, 'modification_id');
    }

    public function wheelSpecs(): HasMany
    {
        return $this->hasMany(VehicleWheelSpec::class, 'modification_id');
    }
}
