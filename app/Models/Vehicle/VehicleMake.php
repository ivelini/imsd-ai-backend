<?php

namespace App\Models\Vehicle;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Марка автомобиля (BMW, Toyota…). */
class VehicleMake extends Model
{
    protected $fillable = ['name'];

    public function models(): HasMany
    {
        return $this->hasMany(VehicleModel::class, 'make_id');
    }
}
