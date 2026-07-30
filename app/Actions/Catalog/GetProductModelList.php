<?php

namespace App\Actions\Catalog;

use App\Models\Catalog\ProductModel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/** Список моделей с фильтрацией и пагинацией. */
final readonly class GetProductModelList
{
    private const array ALLOWED_SORT = ['id', 'name', 'type', 'created_at'];

    public function execute(array $params): LengthAwarePaginator
    {
        $perPage = min(max((int) ($params['per_page'] ?? 50), 10), 100);

        $query = ProductModel::with('brand')
            ->withCount(['tireProducts', 'wheelProducts']);

        if (! empty($params['type'])) {
            $query->byType($params['type']);
        }

        if (! empty($params['brand_id'])) {
            $query->where('brand_id', $params['brand_id']);
        }

        $sortBy = in_array($params['sort_by'] ?? 'name', self::ALLOWED_SORT, true)
            ? ($params['sort_by'] ?? 'name')
            : 'name';
        $sortDir = ($params['sort_dir'] ?? 'asc') === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sortBy, $sortDir)->paginate($perPage);
    }
}
