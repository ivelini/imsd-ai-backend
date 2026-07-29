<?php

namespace App\Models\Catalog;

use App\Enums\Catalog\ProductType;
use Database\Factories\Catalog\BrandFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Бренд товара (Winter Drive…). Привязан к одному поставщику. */
class Brand extends Model
{
    /** @use HasFactory<BrandFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'logo',
        'description',
        'type',
    ];

    public function tireProducts(): HasMany
    {
        return $this->hasMany(TireProduct::class);
    }

    public function wheelProducts(): HasMany
    {
        return $this->hasMany(WheelProduct::class);
    }

    public function isTireBrand(): bool
    {
        return in_array($this->type, [ProductType::Tire->value, 'both']);
    }

    public function isWheelBrand(): bool
    {
        return in_array($this->type, [ProductType::Wheel->value, 'both']);
    }

    /** Поиск по названию бренда. */
    public function scopeSearch(Builder $query, string $search): void
    {
        $query->where('name', 'like', '%'.$search.'%');
    }

    /** Фильтр по типу бренда (tire, wheel, both). */
    public function scopeByType(Builder $query, string $type): void
    {
        $query->where('type', $type);
    }
}
