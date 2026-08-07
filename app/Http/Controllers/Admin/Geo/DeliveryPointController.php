<?php

namespace App\Http\Controllers\Admin\Geo;

use App\Actions\Geo\GetDeliveryPointList;
use App\Http\Requests\Admin\Geo\DeliveryPointIndexRequest;
use App\Http\Requests\Admin\Geo\DeliveryPointRequest;
use App\Http\Resources\Admin\Geo\DeliveryPointResource;
use App\Models\Delivery\DeliveryPoint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** CRUD точек выдачи. */
final readonly class DeliveryPointController
{
    public function __construct(
        private GetDeliveryPointList $getDeliveryPointList,
    ) {}

    public function index(DeliveryPointIndexRequest $request): AnonymousResourceCollection
    {
        return DeliveryPointResource::collection(
            $this->getDeliveryPointList->execute($request->validated())
        );
    }

    public function store(DeliveryPointRequest $request): JsonResponse
    {
        $point = DeliveryPoint::create($request->validated());

        return (new DeliveryPointResource($point))->response()->setStatusCode(201);
    }

    public function show(int $id): DeliveryPointResource
    {
        return new DeliveryPointResource(
            DeliveryPoint::with('city')->findOrFail($id)
        );
    }

    public function update(DeliveryPointRequest $request, int $id): DeliveryPointResource
    {
        $point = DeliveryPoint::findOrFail($id);
        $point->update($request->validated());

        return new DeliveryPointResource($point->load('city'));
    }

    public function destroy(int $id): JsonResponse
    {
        DeliveryPoint::findOrFail($id)->delete();

        return response()->json(null, 204);
    }
}
