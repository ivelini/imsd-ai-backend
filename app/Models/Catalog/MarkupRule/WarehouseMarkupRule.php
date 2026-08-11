<?php

namespace App\Models\Catalog\MarkupRule;

use App\Models\Catalog\Warehouse\Warehouse;
use Database\Factories\Catalog\MarkupRule\WarehouseMarkupRuleFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Правило наценки склада: диапазон закупочной цены → коэффициент. */
class WarehouseMarkupRule extends Model
{
    /** @use HasFactory<WarehouseMarkupRuleFactory> */
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'price_from',
        'price_to',
        'coefficient',
    ];

    protected function casts(): array
    {
        return [
            'coefficient' => 'float',
            'price_from' => 'float',
            'price_to' => 'float',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /** Фильтр по складу. */
    #[Scope]
    protected function byWarehouse(Builder $query, int $warehouseId): void
    {
        $query->where('warehouse_id', $warehouseId);
    }
}
