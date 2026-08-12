<?php

namespace App\Models\Catalog\Tire;

use App\Casts\SeasonCast;
use App\Enums\Catalog\Season;
use App\Models\Catalog\Brand\Brand;
use App\Models\Catalog\Builders\TireProductBuilder;
use App\Models\Catalog\Country\Country;
use App\Models\Catalog\Model\ProductModel;
use App\Models\Catalog\Promotion\Promotion;
use App\Models\Catalog\Supplier\Supplier;
use App\Models\Catalog\Warehouse\Stock;
use App\Models\Image;
use Carbon\Carbon;
use Database\Factories\Catalog\Tire\TireProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Шина: характеристика, бренд, страна, сезонность.
 *
 * @property int $id
 * @property int $brand_id
 * @property int|null $model_id
 * @property string $name
 * @property int|null $supplier_id
 * @property int|null $country_id
 * @property string|null $ean
 * @property Season|null $season
 * @property int|null $width
 * @property int|null $profile
 * @property string|null $diameter
 * @property string|null $load_index
 * @property string|null $speed_index
 * @property bool $is_studded
 * @property bool $is_runflat
 * @property bool $is_xl
 * @property int|null $year
 * @property string|null $description
 * @property bool $is_published
 * @property bool $is_bestseller
 * @property bool $is_new
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Brand $brand
 * @property-read ProductModel|null $model
 * @property-read Supplier|null $supplier
 * @property-read Country|null $country
 */
class TireProduct extends Model
{
    /** @use HasFactory<TireProductFactory> */
    use HasFactory;

    protected $fillable = [
        'brand_id',
        'model_id',
        'name',
        'supplier_id',
        'country_id',
        'ean',
        'season',
        'width',
        'profile',
        'diameter',
        'load_index',
        'speed_index',
        'is_studded',
        'is_runflat',
        'is_xl',
        'year',
        'description',
        'is_published',
        'is_bestseller',
        'is_new',
    ];

    protected function casts(): array
    {
        return [
            'season' => SeasonCast::class,
            'width' => 'integer',
            'profile' => 'integer',
            'is_studded' => 'boolean',
            'is_runflat' => 'boolean',
            'is_xl' => 'boolean',
            'is_published' => 'boolean',
            'is_bestseller' => 'boolean',
            'is_new' => 'boolean',
            'year' => 'integer',
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

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
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

    public function newEloquentBuilder($query): TireProductBuilder
    {
        return new TireProductBuilder($query);
    }
}
