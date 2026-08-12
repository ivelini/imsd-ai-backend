<?php

namespace App\Http\Controllers\Admin\Catalog\Warehouse;

use App\Actions\Warehouse\GetWarehouseList;
use App\Http\Requests\Admin\Catalog\Warehouse\WarehouseIndexRequest;
use App\Http\Requests\Admin\Catalog\Warehouse\WarehouseRequest;
use App\Http\Resources\Admin\Catalog\Warehouse\WarehouseResource;
use App\Models\Catalog\Warehouse\Warehouse;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** CRUD складов.
 *
 */
#[Group('Каталог / склады', weight: 50)]
final readonly class WarehouseController
{
    public function __construct(
        private GetWarehouseList $getWarehouseList,
    ) {}

    public function index(WarehouseIndexRequest $request): AnonymousResourceCollection
    {
        return WarehouseResource::collection(
            $this->getWarehouseList->execute($request->validated())
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
