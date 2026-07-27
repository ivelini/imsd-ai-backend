<?php

namespace App\Models\Catalog;

use App\Casts\SeasonCast;
use App\Models\Image;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/** Шина: характеристика, бренд, страна, сезонность. */
class TireProduct extends Model
{
    protected $fillable = [
        'brand_id',
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
        return $this->morphMany(Image::class, 'promotable');
    }
}
