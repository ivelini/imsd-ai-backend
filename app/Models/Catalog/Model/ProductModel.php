<?php

namespace App\Models\Catalog\Model;

use App\Models\Catalog\Brand\Brand;
use App\Models\Catalog\Tire\TireProduct;
use App\Models\Catalog\Wheel\WheelProduct;
use Carbon\Carbon;
use Database\Factories\Catalog\Model\ProductModelFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Модель товара (A503, LS 131). Принадлежит бренду.
 *
 * @property int $id
 * @property int $brand_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $image
 * @property string $type
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Brand $brand
 */
class ProductModel extends Model
{
    /** @use HasFactory<ProductModelFactory> */
    use HasFactory;

    protected $fillable = [
        'brand_id',
        'name',
        'slug',
        'description',
        'image',
        'type',
    ];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function tireProducts(): HasMany
    {
        return $this->hasMany(TireProduct::class, 'model_id');
    }

    public function wheelProducts(): HasMany
    {
        return $this->hasMany(WheelProduct::class, 'model_id');
    }

    /** Фильтр по типу (tire, wheel). */
    #[Scope]
    protected function byType(Builder $query, string $type): void
    {
        $query->where('type', $type);
    }
}
