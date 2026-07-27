<?php

namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/** Акция: скидка на товар, бренд или весь каталог.
 *
 * @property-read Carbon $starts_at
 * @property-read Carbon $ends_at
 */
class Promotion extends Model
{
    protected $fillable = [
        'name',
        'description',
        'type',
        'value',
        'starts_at',
        'ends_at',
        'promotable_type',
        'promotable_id',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function promotable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeActive($query)
    {
        return $query->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now());
    }
}
