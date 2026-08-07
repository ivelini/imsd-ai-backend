<?php

namespace App\Models\Delivery;

use App\Models\Catalog\Warehouse\Stock;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Предрассчитанная финальная цена товара для города. */
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
