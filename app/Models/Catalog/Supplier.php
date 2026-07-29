<?php

namespace App\Models\Catalog;

use Database\Factories\Catalog\SupplierFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Завод-производитель (Cordiant, Nokian…). Внутренний справочник. */
class Supplier extends Model
{
    /** @use HasFactory<SupplierFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
    ];

    public function tireProducts(): HasMany
    {
        return $this->hasMany(TireProduct::class);
    }

    public function wheelProducts(): HasMany
    {
        return $this->hasMany(WheelProduct::class);
    }

    /** Поиск по названию или коду поставщика. */
    public function scopeSearch(Builder $query, string $search): void
    {
        $q = '%'.$search.'%';
        $query->where(function (Builder $qry) use ($q) {
            $qry->where('name', 'like', $q)->orWhere('code', 'like', $q);
        });
    }
}
