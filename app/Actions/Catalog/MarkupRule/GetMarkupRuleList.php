<?php

namespace App\Actions\Catalog\MarkupRule;

use App\Models\Catalog\MarkupRule\WarehouseMarkupRule;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/** Список правил наценки с фильтрацией и пагинацией. */
final readonly class GetMarkupRuleList
{
    private const array ALLOWED_SORT = ['id', 'warehouse_id', 'price_from', 'price_to', 'coefficient', 'created_at'];

    public function execute(array $params): LengthAwarePaginator
    {
        $perPage = min(max((int) ($params['per_page'] ?? 50), 10), 100);

        $query = WarehouseMarkupRule::with('warehouse');

        if (! empty($params['warehouse_id'])) {
            $query->byWarehouse((int) $params['warehouse_id']);
        }

        $sortBy = in_array($params['sort_by'] ?? 'warehouse_id', self::ALLOWED_SORT, true) ? ($params['sort_by'] ?? 'warehouse_id') : 'warehouse_id';
        $sortDir = ($params['sort_dir'] ?? 'asc') === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sortBy, $sortDir)->paginate($perPage);
    }
}
