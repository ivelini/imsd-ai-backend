<?php

namespace App\Actions\Geo;

use App\Models\Delivery\CityPriceRule;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/** Список правил наценки по городам с фильтрацией и пагинацией. */
final readonly class GetCityPriceRuleList
{
    private const array ALLOWED_SORT = ['id', 'city_id', 'price_from', 'price_to', 'markup', 'created_at'];

    public function execute(array $params): LengthAwarePaginator
    {
        $perPage = min(max((int) ($params['per_page'] ?? 50), 10), 100);

        $query = CityPriceRule::with('city');

        if (! empty($params['city_id'])) {
            $query->where('city_id', (int) $params['city_id']);
        }

        $sortBy = in_array($params['sort_by'] ?? 'id', self::ALLOWED_SORT, true) ? ($params['sort_by'] ?? 'id') : 'id';
        $sortDir = ($params['sort_dir'] ?? 'asc') === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sortBy, $sortDir)->paginate($perPage);
    }
}
