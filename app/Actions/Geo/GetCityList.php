<?php

namespace App\Actions\Geo;

use App\Models\Delivery\City;
use Illuminate\Database\Eloquent\Collection;

/** Список городов с фильтрацией. */
final readonly class GetCityList
{
    public function execute(array $params): Collection
    {
        $query = City::with('region');

        if (! empty($params['search'])) {
            $query->where('name', 'like', '%'.$params['search'].'%');
        }

        if (! empty($params['region_code'])) {
            $query->whereHas('region', fn ($q) => $q->where('code', $params['region_code']));
        }

        return $query->orderBy('sort')->orderBy('name')->get();
    }
}
