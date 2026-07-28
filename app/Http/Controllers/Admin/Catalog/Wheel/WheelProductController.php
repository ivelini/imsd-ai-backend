<?php

namespace App\Http\Controllers\Admin\Catalog\Wheel;

use App\Http\Requests\Admin\Catalog\WheelProductIndexRequest;
use App\Http\Requests\Admin\Catalog\WheelProductRequest;
use App\Http\Resources\Admin\Catalog\WheelProductResource;
use App\Models\Catalog\WheelProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** CRUD дисков. */
final readonly class WheelProductController
{
    /** Поля, доступные для сортировки в списке. */
    private const array ALLOWED_SORT = ['id', 'name', 'ean', 'type', 'color', 'pcd', 'et', 'hub_diameter', 'width', 'diameter', 'is_published', 'created_at'];

    /**
     * @group Диски
     */
    public function index(WheelProductIndexRequest $request): AnonymousResourceCollection
    {
        $data = $request->validated();
        $perPage = min(max((int) ($data['per_page'] ?? 50), 10), 100);

        $query = WheelProduct::with('brand');

        if (! empty($data['search'])) {
            $q = '%'.$data['search'].'%';
            $query->where(function ($qry) use ($q) {
                $qry->where('name', 'like', $q)->orWhere('ean', 'like', $q);
            });
        }

        if (! empty($data['brand_id'])) {
            $query->where('brand_id', (int) $data['brand_id']);
        }

        if (! empty($data['type'])) {
            $query->where('type', $data['type']);
        }

        if (! empty($data['color'])) {
            $query->where('color', $data['color']);
        }

        if (isset($data['is_published'])) {
            $query->where('is_published', filter_var($data['is_published'], FILTER_VALIDATE_BOOLEAN));
        }

        $sortBy = in_array($data['sort_by'] ?? 'id', self::ALLOWED_SORT, true) ? $data['sort_by'] : 'id';
        $sortDir = ($data['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return WheelProductResource::collection(
            $query->orderBy($sortBy, $sortDir)->paginate($perPage)
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
