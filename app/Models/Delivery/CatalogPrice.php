<?php

namespace App\Models\Delivery;

use App\Models\Catalog\Warehouse\Stock;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Предрассчитанная финальная цена товара для города.
 *
 * @property int $id
 * @property int $stock_id
 * @property int $city_id
 * @property float $price
 * @property int|null $delivery_min
 * @property int|null $delivery_max
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Stock $stock
 * @property-read City $city
 */
class CatalogPrice extends Model
{
    protected $fillable = [
        'stock_id',
        'city_id',
        'price',
        'delivery_min',
        'delivery_max',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'float',
            'delivery_min' => 'integer',
            'delivery_max' => 'integer',
        ];
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
