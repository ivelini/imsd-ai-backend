<?php

namespace App\Models\Catalog;

use App\Models\Delivery\CatalogPrice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/** Остаток товара на складе: полиморф (шина/диск).
 *
 * @property int|null $deliveryDays Устанавливается перед Resource для сериализации.
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
