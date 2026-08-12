<?php

namespace App\Models\Catalog\Warehouse;

use App\Models\Catalog\MarkupRule\WarehouseMarkupRule;
use App\Models\Delivery\DeliverySchedule;
use Carbon\Carbon;
use Database\Factories\Catalog\Warehouse\WarehouseFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Склад — крупный продавец/дистрибьютор, у которого мы покупаем товар.
 *
 * @property int $id
 * @property string $name
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Collection<int, DeliverySchedule> $deliverySchedules
 */
class Warehouse extends Model
{
    /** @use HasFactory<WarehouseFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    public function markupRules(): HasMany
    {
        return $this->hasMany(WarehouseMarkupRule::class);
    }

    public function deliverySchedules(): HasMany
    {
        return $this->hasMany(DeliverySchedule::class);
    }

    /** Поиск по названию склада. */
    #[Scope]
    protected function search(Builder $query, string $search): void
    {
        $query->where('name', 'like', '%'.$search.'%');
    }
}
