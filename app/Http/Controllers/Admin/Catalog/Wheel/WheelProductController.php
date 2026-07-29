<?php

namespace App\Http\Controllers\Admin\Catalog\Wheel;

use App\Actions\Catalog\GetWheelProductList;
use App\Http\Requests\Admin\Catalog\Wheel\WheelProductIndexRequest;
use App\Http\Requests\Admin\Catalog\Wheel\WheelProductRequest;
use App\Http\Resources\Admin\Catalog\Wheel\WheelProductResource;
use App\Models\Catalog\WheelProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** CRUD дисков. */
final readonly class WheelProductController
{
    public function __construct(
        private GetWheelProductList $getWheelProductList,
    ) {}

    /**
     * @group Диски
     */
    public function index(WheelProductIndexRequest $request): AnonymousResourceCollection
    {
        return WheelProductResource::collection(
            $this->getWheelProductList->execute($request->validated())
        );
    }

    /**
     * @group Диски
     */
    public function show(int $id): WheelProductResource
    {
        return new WheelProductResource(
            WheelProduct::with('brand')->findOrFail($id)
        );
    }

    /**
     * @group Диски
     */
    public function store(WheelProductRequest $request): JsonResponse
    {
        $wheel = WheelProduct::create($request->validated());

        return (new WheelProductResource($wheel))->response()->setStatusCode(201);
    }

    /**
     * @group Диски
     */
    public function update(WheelProductRequest $request, int $id): WheelProductResource
    {
        $wheel = WheelProduct::findOrFail($id);
        $wheel->update($request->validated());

        return new WheelProductResource($wheel->load('brand'));
    }

    /**
     * @group Диски
     */
    public function destroy(int $id): JsonResponse
    {
        WheelProduct::findOrFail($id)->delete();

        return response()->json(null, 204);
    }
}
