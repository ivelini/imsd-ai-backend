<?php

namespace App\Actions\Delivery;

use App\Models\Delivery\DeliverySchedule;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/** Список графиков отгрузки с фильтрацией и пагинацией. */
final readonly class GetDeliveryScheduleList
{
    private const array ALLOWED_SORT = ['id', 'warehouse_id', 'day_of_week', 'created_at'];

    public function execute(array $params): LengthAwarePaginator
    {
        $perPage = min(max((int) ($params['per_page'] ?? 50), 10), 100);

        $query = DeliverySchedule::with('warehouse');

        if (! empty($params['warehouse_id'])) {
            $query->where('warehouse_id', (int) $params['warehouse_id']);
        }

        $sortBy = in_array($params['sort_by'] ?? 'warehouse_id', self::ALLOWED_SORT, true) ? ($params['sort_by'] ?? 'warehouse_id') : 'warehouse_id';
        $sortDir = ($params['sort_dir'] ?? 'asc') === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sortBy, $sortDir)->paginate($perPage);
    }
}
