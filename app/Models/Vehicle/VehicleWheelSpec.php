<?php

namespace App\Models\Vehicle;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Спецификация диска для модификации автомобиля.
 *
 * @property int $id
 * @property int $modification_id
 * @property string $type
 * @property string|null $position
 * @property string|null $width
 * @property int|null $diameter
 * @property string|null $et
 * @property string|null $pcd
 * @property string|null $hub_diameter
 * @property string|null $bolts
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read VehicleModification $modification
 */
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
