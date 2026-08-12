<?php

namespace App\Models\Catalog\Warehouse;

use App\Models\Delivery\CatalogPrice;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Остаток товара на складе: полиморф (шина/диск).
 *
 * @property int $id
 * @property string $stockable_type
 * @property int $stockable_id
 * @property int $warehouse_id
 * @property int $quantity
 * @property string|null $purchase_price
 * @property string|null $price
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property int|null $deliveryDays Устанавливается перед Resource для сериализации.
 * @property-read Warehouse $warehouse
 */
class Stock extends Model
{
    protected $fillable = [
        'stockable_type',
        'stockable_id',
        'warehouse_id',
        'quantity',
        'purchase_price',
        'price',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'purchase_price' => 'decimal:2',
            'price' => 'decimal:2',
        ];
    }

    public function stockable(): MorphTo
    {
        return $this->morphTo();
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function catalogPrices(): HasMany
    {
        return $this->hasMany(CatalogPrice::class);
    }
}
