<?php

namespace App\Models\Delivery;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Город в регионе.
 *
 * @property int $id
 * @property int $region_id
 * @property string $name
 * @property int $sort
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Region $region
 */
class City extends Model
{
    protected $fillable = [
        'region_id',
        'name',
        'sort',
    ];

    protected function casts(): array
    {
        return [
            'sort' => 'integer',
        ];
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function deliveryPoints(): HasMany
    {
        return $this->hasMany(DeliveryPoint::class);
    }

    public function priceRules(): HasMany
    {
        return $this->hasMany(CityPriceRule::class);
    }

    public function catalogPrices(): HasMany
    {
        return $this->hasMany(CatalogPrice::class);
    }

    public function deliveryTimes(): HasMany
    {
        return $this->hasMany(CityDeliveryTime::class);
    }
}
