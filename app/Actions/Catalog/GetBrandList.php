<?php

namespace App\Actions\Catalog;

use App\Models\Catalog\Brand;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/** Список брендов с фильтрацией и пагинацией. */
final readonly class GetBrandList
{
    private const array ALLOWED_SORT = ['id', 'name', 'type', 'created_at'];

    public function execute(array $params): LengthAwarePaginator
    {
        $perPage = min(max((int) ($params['per_page'] ?? 50), 10), 100);

        $query = Brand::withCount(['tireProducts', 'wheelProducts']);

        if (! empty($params['search'])) {
            $query->search($params['search']);
        }

        if (! empty($params['type'])) {
            $query->byType($params['type']);
        }

        $sortBy = in_array($params['sort_by'] ?? 'name', self::ALLOWED_SORT, true) ? ($params['sort_by'] ?? 'name') : 'name';
        $sortDir = ($params['sort_dir'] ?? 'asc') === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sortBy, $sortDir)->paginate($perPage);
    }
}
