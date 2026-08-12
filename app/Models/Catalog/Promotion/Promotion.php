<?php

namespace App\Models\Catalog\Promotion;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Акция: скидка на товар, бренд или весь каталог.
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string $type
 * @property string|null $value
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property string|null $promotable_type
 * @property int|null $promotable_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
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
    #[Scope]
    protected function search(Builder $query, string $search): void
    {
        $query->where('name', 'like', '%'.$search.'%');
    }

    /** Фильтр по типу акции. */
    #[Scope]
    protected function byType(Builder $query, string $type): void
    {
        $query->where('type', $type);
    }

    /** Фильтр по типу привязки (morph-тип). */
    #[Scope]
    protected function byPromotableType(Builder $query, string $promotableType): void
    {
        $query->where('promotable_type', $promotableType);
    }

    /** Активные сейчас (starts_at <= now <= ends_at). */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now());
    }

    /** Неактивные сейчас (ещё не начались или уже закончились). */
    #[Scope]
    protected function inactive(Builder $query): void
    {
        $query->where(function (Builder $q) {
            $q->where('starts_at', '>', now())->orWhere('ends_at', '<', now());
        });
    }
}
