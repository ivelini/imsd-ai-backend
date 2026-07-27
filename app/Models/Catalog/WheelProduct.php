<?php

namespace App\Models\Catalog;

use App\Casts\WheelTypeCast;
use App\Models\Image;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/** Диск: тип, цвет, PCD, ET, DIA. */
class WheelProduct extends Model
{
    protected $fillable = [
        'brand_id',
        'name',
        'supplier_id',
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
            'is_published' => 'boolean',
            'is_bestseller' => 'boolean',
            'is_new' => 'boolean',
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
