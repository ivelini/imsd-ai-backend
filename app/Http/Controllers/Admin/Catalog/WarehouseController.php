<?php

namespace App\Http\Controllers\Admin\Catalog;

use App\Http\Requests\Admin\Catalog\WarehouseIndexRequest;
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
    /** Поля, доступные для сортировки в списке. */
    private const array ALLOWED_SORT = ['id', 'name', 'created_at'];

    public function index(WarehouseIndexRequest $request): AnonymousResourceCollection
    {
        $data = $request->validated();
        $perPage = min(max((int) ($data['per_page'] ?? 50), 10), 100);

        $query = Warehouse::query();

        if (! empty($data['search'])) {
            $query->where('name', 'like', '%'.$data['search'].'%');
        }

        $sortBy = in_array($data['sort_by'] ?? 'name', self::ALLOWED_SORT, true) ? $data['sort_by'] : 'name';
        $sortDir = ($data['sort_dir'] ?? 'asc') === 'asc' ? 'asc' : 'desc';

        return WarehouseResource::collection(
            $query->orderBy($sortBy, $sortDir)->paginate($perPage)
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
