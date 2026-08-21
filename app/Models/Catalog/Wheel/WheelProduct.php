<?php

namespace App\Models\Catalog\Wheel;

use App\Casts\WheelTypeCast;
use App\Enums\Catalog\WheelType;
use App\Models\Catalog\Brand\Brand;
use App\Models\Catalog\Builders\WheelProductBuilder;
use App\Models\Catalog\Country\Country;
use App\Models\Catalog\Model\ProductModel;
use App\Models\Catalog\Promotion\Promotion;
use App\Models\Catalog\Warehouse\Stock;
use App\Models\Image;
use Carbon\Carbon;
use Database\Factories\Catalog\Wheel\WheelProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Диск: тип, цвет, PCD, ET, DIA.
 *
 * @property int $id
 * @property int $brand_id
 * @property int|null $model_id
 * @property string $name
 * @property string|null $slug URL-часть из характеристик (brand-name-width-diameter-et-pcd-hub_diameter)
 * @property int|null $country_id
 * @property string|null $ean
 * @property WheelType|null $type
 * @property string|null $color
 * @property string|null $pcd
 * @property string|null $et
 * @property string|null $hub_diameter
 * @property string|null $width
 * @property int|null $diameter
 * @property string|null $description
 * @property bool $is_published
 * @property bool $is_bestseller
 * @property bool $is_new
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Brand $brand
 * @property-read ProductModel|null $model
 * @property-read Country|null $country
 * @property float|null $city_price Цена города из catalog_prices (transient, ставит GetWheelList)
 * @property int|null $city_delivery_min Срок доставки города (transient, ставит GetWheelList)
 * @property int|null $city_delivery_max Срок доставки города (transient, ставит GetWheelList)
 */
class WheelProduct extends Model
{
    /** @use HasFactory<WheelProductFactory> */
    use HasFactory;

    protected $fillable = [
        'brand_id',
        'model_id',
        'name',
        'slug',
        'country_id',
        'ean',
        'type',
        'color',
        'pcd',
        'et',
        'hub_diameter',
        'width',
        'diameter',
        'description',
        'is_published',
        'is_bestseller',
        'is_new',
    ];

    protected function casts(): array
    {
        return [
            'type' => WheelTypeCast::class,
            // decimal:1 — стабильный строковый формат на чтении/записи
            // (pgsql отдаёт '38.0', sqlite в тестах теряет дробную часть: 38)
            'width' => 'decimal:1',
            'et' => 'decimal:1',
            'hub_diameter' => 'decimal:1',
            'is_published' => 'boolean',
            'is_bestseller' => 'boolean',
            'is_new' => 'boolean',
        ];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function model(): BelongsTo
    {
        return $this->belongsTo(ProductModel::class, 'model_id');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function stocks(): MorphMany
    {
        return $this->morphMany(Stock::class, 'stockable');
    }

    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function promotions(): MorphMany
    {
        return $this->morphMany(Promotion::class, 'promotable');
    }

    public function newEloquentBuilder($query): WheelProductBuilder
    {
        return new WheelProductBuilder($query);
    }
}
