<?php

namespace App\Models\Catalog\Brand;

use App\Enums\Catalog\BrandType;
use App\Enums\Catalog\ProductType;
use App\Models\Catalog\Model\ProductModel;
use App\Models\Catalog\Tire\TireProduct;
use App\Models\Catalog\Wheel\WheelProduct;
use Carbon\Carbon;
use Database\Factories\Catalog\Brand\BrandFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Бренд товара (Winter Drive…).
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $logo
 * @property string|null $description
 * @property BrandType $type
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
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

    protected function casts(): array
    {
        return [
            'type' => BrandType::class,
        ];
    }

    public function models(): HasMany
    {
        return $this->hasMany(ProductModel::class);
    }

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
        return $this->type->covers(ProductType::Tire);
    }

    public function isWheelBrand(): bool
    {
        return $this->type->covers(ProductType::Wheel);
    }

    /** Поиск по названию бренда. */
    #[Scope]
    protected function search(Builder $query, string $search): void
    {
        $query->where('name', 'like', '%'.$search.'%');
    }

    /** Фильтр по типу бренда (tire, wheel, both). */
    #[Scope]
    protected function byType(Builder $query, string $type): void
    {
        $query->where('type', $type);
    }
}
