<?php

namespace App\Models\Catalog;

use App\Enums\ProductType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Бренд товара (Winter Drive…). Привязан к одному поставщику. */
class Brand extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'logo',
        'description',
        'type',
    ];

    public function tireProducts(): HasMany
    {
        return $this->hasMany(TireProduct::class);
    }

    public function wheelProducts(): HasMany
    {
        return $this->hasMany(WheelProduct::class);
    }

    public function isTireBrand(): bool
    {
        return in_array($this->type, [ProductType::Tire->value, 'both']);
    }

    public function isWheelBrand(): bool
    {
        return in_array($this->type, [ProductType::Wheel->value, 'both']);
    }
}
