<?php

namespace App\Actions\Geo;

use App\Models\Delivery\City;
use Illuminate\Database\Eloquent\Collection;

/** Справочник городов для дропдаунов публичного API: id, name, slug; сортировка по имени. */
final readonly class GetCityReference
{
    public function execute(): Collection
    {
        return City::query()
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);
    }
}
