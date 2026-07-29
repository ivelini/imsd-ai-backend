<?php

namespace App\Http\Controllers\Admin\Catalog\Tire;

use App\Http\Requests\Admin\Catalog\Tire\TireProductIndexRequest;
use App\Http\Requests\Admin\Catalog\Tire\TireProductRequest;
use App\Http\Resources\Admin\Catalog\Tire\TireProductResource;
use App\Models\Catalog\TireProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** CRUD шин. */
final readonly class TireProductController
{
    /** Поля, доступные для сортировки в списке. */
    private const array ALLOWED_SORT = ['id', 'name', 'ean', 'season', 'width', 'profile', 'diameter', 'load_index', 'speed_index', 'year', 'is_published', 'created_at'];

    /**
     * Список шин.
     *
     * @group Шины
     */
    public function index(TireProductIndexRequest $request): AnonymousResourceCollection
    {
        $data = $request->validated();
        $perPage = min(max((int) ($data['per_page'] ?? 50), 10), 100);

        $query = TireProduct::with('brand');

        if (! empty($data['search'])) {
            $q = '%'.$data['search'].'%';
            $query->where(function ($qry) use ($q) {
                $qry->where('name', 'like', $q)->orWhere('ean', 'like', $q);
            });
        }

        if (! empty($data['brand_id'])) {
            $query->where('brand_id', (int) $data['brand_id']);
        }

        if (! empty($data['season'])) {
            $query->where('season', $data['season']);
        }

        if (isset($data['is_published'])) {
            $query->where('is_published', filter_var($data['is_published'], FILTER_VALIDATE_BOOLEAN));
        }

        if (isset($data['is_studded'])) {
            $query->where('is_studded', filter_var($data['is_studded'], FILTER_VALIDATE_BOOLEAN));
        }

        if (isset($data['is_runflat'])) {
            $query->where('is_runflat', filter_var($data['is_runflat'], FILTER_VALIDATE_BOOLEAN));
        }

        if (isset($data['is_xl'])) {
            $query->where('is_xl', filter_var($data['is_xl'], FILTER_VALIDATE_BOOLEAN));
        }

        $sortBy = in_array($data['sort_by'] ?? 'id', self::ALLOWED_SORT, true) ? $data['sort_by'] : 'id';
        $sortDir = ($data['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return TireProductResource::collection(
            $query->orderBy($sortBy, $sortDir)->paginate($perPage)
        );
    }

    /**
     * Получить шину.
     *
     * @group Шины
     */
    public function show(int $id): TireProductResource
    {
        return new TireProductResource(
            TireProduct::with('brand')->findOrFail($id)
        );
    }

    /**
     * Создать шину.
     *
     * @group Шины
     */
    public function store(TireProductRequest $request): JsonResponse
    {
        $tire = TireProduct::create($request->validated());

        return (new TireProductResource($tire))->response()->setStatusCode(201);
    }

    /**
     * Обновить шину.
     *
     * @group Шины
     */
    public function update(TireProductRequest $request, int $id): TireProductResource
    {
        $tire = TireProduct::findOrFail($id);
        $tire->update($request->validated());

        return new TireProductResource($tire->load('brand'));
    }

    /**
     * Удалить шину.
     *
     * @group Шины
     */
    public function destroy(int $id): JsonResponse
    {
        TireProduct::findOrFail($id)->delete();

        return response()->json(null, 204);
    }
}
