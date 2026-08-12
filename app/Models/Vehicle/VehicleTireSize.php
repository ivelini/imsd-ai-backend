<?php

namespace App\Models\Vehicle;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Типоразмер шины для модификации автомобиля.
 *
 * @property int $id
 * @property int $modification_id
 * @property string $type
 * @property string|null $position
 * @property int $width
 * @property int $profile
 * @property string $diameter
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read VehicleModification $modification
 */
class VehicleTireSize extends Model
{
    protected $fillable = [
        'modification_id',
        'type',
        'position',
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
