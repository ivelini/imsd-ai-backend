<?php

namespace App\Models\Vehicle;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Спецификация диска для модификации автомобиля. */
class VehicleWheelSpec extends Model
{
    protected $fillable = [
        'modification_id',
        'type',
        'position',
        'width',
        'diameter',
        'et',
        'pcd',
        'hub_diameter',
        'bolts',
    ];

    protected function casts(): array
    {
        return [
            'width' => 'decimal:1',
            'et' => 'decimal:1',
            'hub_diameter' => 'decimal:1',
            'diameter' => 'integer',
        ];
    }

    public function modification(): BelongsTo
    {
        return $this->belongsTo(VehicleModification::class, 'modification_id');
    }
}
