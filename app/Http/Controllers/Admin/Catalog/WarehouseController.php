<?php

namespace App\Http\Controllers\Admin\Catalog;

use App\Http\Requests\Admin\Catalog\WarehouseRequest;
use App\Http\Resources\Admin\Catalog\WarehouseResource;
use App\Models\Catalog\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** CRUD складов.
 *
 * @group Склады
 */
final readonly class WarehouseController
{
    public function index(): AnonymousResourceCollection
    {
        return WarehouseResource::collection(
            Warehouse::orderBy('name')->paginate(50)
        );
    }

    public function store(WarehouseRequest $request): JsonResponse
    {
        $warehouse = Warehouse::create($request->validated());

        return (new WarehouseResource($warehouse))->response()->setStatusCode(201);
    }

    public function show(int $id): WarehouseResource
    {
        return new WarehouseResource(Warehouse::findOrFail($id));
    }

    public function update(WarehouseRequest $request, int $id): WarehouseResource
    {
        $warehouse = Warehouse::findOrFail($id);
        $warehouse->update($request->validated());

        return new WarehouseResource($warehouse);
    }

    public function destroy(int $id): JsonResponse
    {
        $warehouse = Warehouse::findOrFail($id);
        $warehouse->delete();

        return response()->json(null, 204);
    }
}
