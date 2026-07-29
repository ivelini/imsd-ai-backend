<?php

namespace App\Actions\Catalog;

use App\Models\Catalog\WheelProduct;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/** Список дисков с фильтрацией и пагинацией. */
final readonly class GetWheelProductList
{
    private const array ALLOWED_SORT = ['id', 'name', 'ean', 'type', 'color', 'pcd', 'et', 'hub_diameter', 'width', 'diameter', 'is_published', 'created_at'];

    public function execute(array $params): LengthAwarePaginator
    {
        $perPage = min(max((int) ($params['per_page'] ?? 50), 10), 100);

        $query = WheelProduct::with('brand');

        if (! empty($params['search'])) {
            $query->search($params['search']);
        }

        if (! empty($params['brand_id'])) {
            $query->byBrand((int) $params['brand_id']);
        }

        if (! empty($params['type'])) {
            $query->byType($params['type']);
        }

        if (! empty($params['color'])) {
            $query->byColor($params['color']);
        }

        if (isset($params['is_published'])) {
            $query->published(filter_var($params['is_published'], FILTER_VALIDATE_BOOLEAN));
        }

        $sortBy = in_array($params['sort_by'] ?? 'id', self::ALLOWED_SORT, true) ? ($params['sort_by'] ?? 'id') : 'id';
        $sortDir = ($params['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sortBy, $sortDir)->paginate($perPage);
    }
}
