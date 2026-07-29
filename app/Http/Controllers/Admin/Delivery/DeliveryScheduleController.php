<?php

namespace App\Http\Controllers\Admin\Delivery;

use App\Http\Requests\Admin\Delivery\DeliveryScheduleIndexRequest;
use App\Http\Requests\Admin\Delivery\DeliveryScheduleRequest;
use App\Http\Resources\Admin\Delivery\DeliveryScheduleResource;
use App\Models\Delivery\DeliverySchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** CRUD графиков отгрузки складов. */
final readonly class DeliveryScheduleController
{
    /** Поля, доступные для сортировки в списке. */
    private const array ALLOWED_SORT = ['id', 'warehouse_id', 'day_of_week', 'created_at'];

    /**
     * @group Графики отгрузки
     */
    public function index(DeliveryScheduleIndexRequest $request): AnonymousResourceCollection
    {
        $data = $request->validated();
        $perPage = min(max((int) ($data['per_page'] ?? 50), 10), 100);

        $query = DeliverySchedule::with('warehouse');

        if (! empty($data['warehouse_id'])) {
            $query->where('warehouse_id', (int) $data['warehouse_id']);
        }

        $sortBy = in_array($data['sort_by'] ?? 'warehouse_id', self::ALLOWED_SORT, true) ? $data['sort_by'] : 'warehouse_id';
        $sortDir = ($data['sort_dir'] ?? 'asc') === 'asc' ? 'asc' : 'desc';

        return DeliveryScheduleResource::collection(
            $query->orderBy($sortBy, $sortDir)->paginate($perPage)
        );
    }

    /**
     * @group Графики отгрузки
     */
    public function store(DeliveryScheduleRequest $request): JsonResponse
    {
        $schedule = DeliverySchedule::create($request->validated());

        return (new DeliveryScheduleResource($schedule))->response()->setStatusCode(201);
    }

    /**
     * @group Графики отгрузки
     */
    public function show(int $id): DeliveryScheduleResource
    {
        return new DeliveryScheduleResource(
            DeliverySchedule::with('warehouse')->findOrFail($id)
        );
    }

    /**
     * @group Графики отгрузки
     */
    public function update(DeliveryScheduleRequest $request, int $id): DeliveryScheduleResource
    {
        $schedule = DeliverySchedule::findOrFail($id);
        $schedule->update($request->validated());

        return new DeliveryScheduleResource($schedule->load('warehouse'));
    }

    /**
     * @group Графики отгрузки
     */
    public function destroy(int $id): JsonResponse
    {
        DeliverySchedule::findOrFail($id)->delete();

        return response()->json(null, 204);
    }
}
