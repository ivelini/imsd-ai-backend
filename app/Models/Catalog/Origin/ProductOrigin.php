<?php

namespace App\Models\Catalog\Origin;

use App\Casts\OriginInfoCast;
use App\DTOs\Catalog\OriginInfo;
use App\Models\Catalog\Tire\TireProduct;
use App\Models\Catalog\Wheel\WheelProduct;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Происхождение товара: производитель, страна и год производства ({badge, description}).
 * Одна запись — уникальный триплет (vendor, manufacture_country, manufacture_year).
 *
 * @property int $id
 * @property OriginInfo|null $vendor
 * @property OriginInfo|null $manufacture_country
 * @property OriginInfo|null $manufacture_year
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class ProductOrigin extends Model
{
    protected $fillable = [
        'vendor',
        'manufacture_country',
        'manufacture_year',
    ];

    protected function casts(): array
    {
        return [
            'vendor' => OriginInfoCast::class,
            'manufacture_country' => OriginInfoCast::class,
            'manufacture_year' => OriginInfoCast::class,
        ];
    }

    public function tireProducts(): HasMany
    {
        return $this->hasMany(TireProduct::class, 'origin_id');
    }

    public function wheelProducts(): HasMany
    {
        return $this->hasMany(WheelProduct::class, 'origin_id');
    }
}
