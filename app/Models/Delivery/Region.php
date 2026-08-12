<?php

namespace App\Models\Delivery;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Регион (субъект РФ).
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
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
