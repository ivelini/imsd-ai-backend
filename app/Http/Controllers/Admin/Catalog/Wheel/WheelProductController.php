<?php

namespace App\Http\Controllers\Admin\Catalog\Wheel;

use App\Http\Requests\Admin\Catalog\WheelProductRequest;
use App\Http\Resources\Admin\Catalog\WheelProductResource;
use App\Models\Catalog\WheelProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** CRUD дисков. */
final readonly class WheelProductController
{
    /**
     * @group Диски
     */
    public function index(): AnonymousResourceCollection
    {
        return WheelProductResource::collection(
            WheelProduct::with('brand')->orderBy('id', 'desc')->paginate(50)
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
