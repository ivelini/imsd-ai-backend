<?php

namespace App\Actions\Catalog;

use App\Models\Catalog\Warehouse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/** Список складов с фильтрацией и пагинацией. */
final readonly class GetWarehouseList
{
    private const array ALLOWED_SORT = ['id', 'name', 'created_at'];

    public function execute(array $params): LengthAwarePaginator
    {
        $perPage = min(max((int) ($params['per_page'] ?? 50), 10), 100);

        $query = Warehouse::query();

        if (! empty($params['search'])) {
            $query->search($params['search']);
        }

        $sortBy = in_array($params['sort_by'] ?? 'name', self::ALLOWED_SORT, true) ? $params['sort_by'] : 'name';
        $sortDir = ($params['sort_dir'] ?? 'asc') === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sortBy, $sortDir)->paginate($perPage);
    }
}
