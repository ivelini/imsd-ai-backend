<?php

namespace App\Actions\Catalog\Supplier;

use App\Models\Catalog\Supplier\Supplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/** Список поставщиков с фильтрацией и пагинацией. */
final readonly class GetSupplierList
{
    private const array ALLOWED_SORT = ['id', 'name', 'code', 'created_at'];

    public function execute(array $params): LengthAwarePaginator
    {
        $perPage = min(max((int) ($params['per_page'] ?? 50), 10), 100);

        $query = Supplier::query();

        if (! empty($params['search'])) {
            $query->search($params['search']);
        }

        $sortBy = in_array($params['sort_by'] ?? 'name', self::ALLOWED_SORT, true) ? ($params['sort_by'] ?? 'name') : 'name';
        $sortDir = ($params['sort_dir'] ?? 'asc') === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sortBy, $sortDir)->paginate($perPage);
    }
}
