<?php

namespace App\Actions\Geo;

use App\Models\Delivery\City;
use Illuminate\Database\Eloquent\Collection;

/** Справочник городов для дропдаунов публичного API: id, name, slug; сортировка по имени. */
final readonly class GetCityReference
{
    public function execute(): Collection
    {
        // region_id обязателен в select — без него eager load не сматчит реляцию
        return City::query()
            ->with('region')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'region_id']);
    }

    /** Город по умолчанию из config/shop.php (null — города нет в БД). */
    public function defaultCity(string $name): ?City
    {
        return City::query()
            ->where('name', $name)
            ->first(['id', 'name']);
    }
}
