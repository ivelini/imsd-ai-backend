<?php

namespace App\Actions\Catalog;

use App\Models\Catalog\Promotion;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/** Список акций с фильтрацией и пагинацией. */
final readonly class GetPromotionList
{
    private const array ALLOWED_SORT = ['id', 'name', 'type', 'value', 'starts_at', 'ends_at', 'created_at'];

    public function execute(array $params): LengthAwarePaginator
    {
        $perPage = min(max((int) ($params['per_page'] ?? 50), 10), 100);

        $query = Promotion::query();

        if (! empty($params['search'])) {
            $query->search($params['search']);
        }

        if (! empty($params['type'])) {
            $query->byType($params['type']);
        }

        if (! empty($params['promotable_type'])) {
            $query->byPromotableType($params['promotable_type']);
        }

        if (isset($params['is_active'])) {
            if (filter_var($params['is_active'], FILTER_VALIDATE_BOOLEAN)) {
                $query->active();
            } else {
                $query->inactive();
            }
        }

        $sortBy = in_array($params['sort_by'] ?? 'starts_at', self::ALLOWED_SORT, true) ? ($params['sort_by'] ?? 'starts_at') : 'starts_at';
        $sortDir = ($params['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sortBy, $sortDir)->paginate($perPage);
    }
}
