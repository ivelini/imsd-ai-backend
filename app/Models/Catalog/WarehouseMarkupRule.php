<?php

namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Правило наценки склада: диапазон закупочной цены → коэффициент. */
class WarehouseMarkupRule extends Model
{
    protected $fillable = [
        'warehouse_id',
        'price_from',
        'price_to',
        'coefficient',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}
