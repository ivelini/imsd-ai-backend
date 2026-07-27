<?php

namespace App\Models\Vehicle;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Типоразмер шины для модификации автомобиля. */
class VehicleTireSize extends Model
{
    protected $fillable = [
        'modification_id',
        'type',
        'width',
        'profile',
        'diameter',
    ];

    protected function casts(): array
    {
        return [
            'width' => 'integer',
            'profile' => 'integer',
        ];
    }

    public function modification(): BelongsTo
    {
        return $this->belongsTo(VehicleModification::class, 'modification_id');
    }
}
