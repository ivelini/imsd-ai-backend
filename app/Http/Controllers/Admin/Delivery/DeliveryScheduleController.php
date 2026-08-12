<?php

namespace App\Http\Controllers\Admin\Delivery;

use App\Actions\Delivery\GetDeliveryScheduleList;
use App\Http\Requests\Admin\Delivery\DeliveryScheduleIndexRequest;
use App\Http\Requests\Admin\Delivery\DeliveryScheduleRequest;
use App\Http\Resources\Admin\Delivery\DeliveryScheduleResource;
use App\Models\Delivery\DeliverySchedule;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** CRUD графиков отгрузки складов. */
#[Group('Каталог / склады / график отгрузки', weight: 50)]
final readonly class DeliveryScheduleController
{
    public function __construct(
        private GetDeliveryScheduleList $getDeliveryScheduleList,
    ) {}

    public function index(DeliveryScheduleIndexRequest $request): AnonymousResourceCollection
    {
        return DeliveryScheduleResource::collection(
            $this->getDeliveryScheduleList->execute($request->validated())
        );
    }

    public function store(DeliveryScheduleRequest $request): JsonResponse
    {
        $schedule = DeliverySchedule::create($request->validated());

        return (new DeliveryScheduleResource($schedule))->response()->setStatusCode(201);
    }

    public function show(int $id): DeliveryScheduleResource
    {
        return new DeliveryScheduleResource(
            DeliverySchedule::with('warehouse')->findOrFail($id)
        );
    }

    public function update(DeliveryScheduleRequest $request, int $id): DeliveryScheduleResource
    {
        $schedule = DeliverySchedule::findOrFail($id);
        $schedule->update($request->validated());

        return new DeliveryScheduleResource($schedule->load('warehouse'));
    }

    public function destroy(int $id): JsonResponse
    {
        DeliverySchedule::findOrFail($id)->delete();

        return response()->json(null, 204);
    }
}
