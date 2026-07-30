<?php

namespace App\Models\Catalog\Promotion;

use Illuminate\Database\Eloquent\Builder;
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

    /** Поиск по названию акции. */
    public function scopeSearch(Builder $query, string $search): void
    {
        $query->where('name', 'like', '%'.$search.'%');
    }

    /** Фильтр по типу акции. */
    public function scopeByType(Builder $query, string $type): void
    {
        $query->where('type', $type);
    }

    /** Фильтр по типу привязки (morph-тип). */
    public function scopeByPromotableType(Builder $query, string $promotableType): void
    {
        $query->where('promotable_type', $promotableType);
    }

    /** Активные сейчас (starts_at <= now <= ends_at). */
    public function scopeActive(Builder $query): void
    {
        $query->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now());
    }

    /** Неактивные сейчас (ещё не начались или уже закончились). */
    public function scopeInactive(Builder $query): void
    {
        $query->where(function (Builder $q) {
            $q->where('starts_at', '>', now())->orWhere('ends_at', '<', now());
        });
    }
}
