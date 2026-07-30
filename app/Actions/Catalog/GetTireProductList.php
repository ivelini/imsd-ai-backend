<?php

namespace App\Actions\Catalog;

use App\Models\Catalog\TireProduct;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/** Список шин с фильтрацией и пагинацией. */
final readonly class GetTireProductList
{
    private const array ALLOWED_SORT = ['id', 'name', 'ean', 'season', 'width', 'profile', 'diameter', 'load_index', 'speed_index', 'year', 'is_published', 'created_at'];

    public function execute(array $params): LengthAwarePaginator
    {
        $perPage = min(max((int) ($params['per_page'] ?? 50), 10), 100);

        $query = TireProduct::with('brand', 'model', 'stocks.warehouse.deliverySchedules');

        if (! empty($params['search'])) {
            $query->search($params['search']);
        }

        if (! empty($params['brand_id'])) {
            $query->byBrand((int) $params['brand_id']);
        }

        if (! empty($params['model_id'])) {
            $query->byModel((int) $params['model_id']);
        }

        if (! empty($params['season'])) {
            $query->bySeason($params['season']);
        }

        if (isset($params['is_published'])) {
            $query->published(filter_var($params['is_published'], FILTER_VALIDATE_BOOLEAN));
        }

        if (isset($params['is_studded'])) {
            $query->studded(filter_var($params['is_studded'], FILTER_VALIDATE_BOOLEAN));
        }

        if (isset($params['is_runflat'])) {
            $query->runflat(filter_var($params['is_runflat'], FILTER_VALIDATE_BOOLEAN));
        }

        if (isset($params['is_xl'])) {
            $query->xl(filter_var($params['is_xl'], FILTER_VALIDATE_BOOLEAN));
        }

        $sortBy = in_array($params['sort_by'] ?? 'id', self::ALLOWED_SORT, true) ? ($params['sort_by'] ?? 'id') : 'id';
        $sortDir = ($params['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sortBy, $sortDir)->paginate($perPage);
    }
}
