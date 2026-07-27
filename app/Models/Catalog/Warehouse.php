<?php

namespace App\Models\Catalog;

use App\Models\Delivery\DeliverySchedule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Склад — крупный продавец/дистрибьютор, у которого мы покупаем товар. */
class Warehouse extends Model
{
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
}
