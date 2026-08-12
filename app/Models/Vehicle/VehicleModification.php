<?php

namespace App\Models\Vehicle;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Модификация автомобиля (двигатель + комплектация).
 *
 * @property int $id
 * @property int $model_id
 * @property string $name
 * @property int|null $year
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read VehicleModel $model
 */
class VehicleModification extends Model
{
    protected $fillable = [
        'model_id',
        'name',
        'year',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
        ];
    }

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
