<?php

namespace App\Actions\Geo;

use App\Models\Delivery\DeliveryPoint;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/** Список точек выдачи с фильтрацией и пагинацией. */
final readonly class GetDeliveryPointList
{
    private const array ALLOWED_SORT = ['id', 'city_id', 'address', 'created_at'];

    public function execute(array $params): LengthAwarePaginator
    {
        $perPage = min(max((int) ($params['per_page'] ?? 50), 10), 100);

        $query = DeliveryPoint::with('city');

        if (! empty($params['city_id'])) {
            $query->where('city_id', (int) $params['city_id']);
        }

        if (! empty($params['search'])) {
            $search = $params['search'];
            $query->where(function ($q) use ($search) {
                $q->where('address', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $sortBy = in_array($params['sort_by'] ?? 'city_id', self::ALLOWED_SORT, true) ? ($params['sort_by'] ?? 'city_id') : 'city_id';
        $sortDir = ($params['sort_dir'] ?? 'asc') === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sortBy, $sortDir)->paginate($perPage);
    }
}
