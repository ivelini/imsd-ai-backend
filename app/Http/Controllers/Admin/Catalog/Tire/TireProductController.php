<?php

namespace App\Http\Controllers\Admin\Catalog\Tire;

use App\Http\Requests\Admin\Catalog\TireProductRequest;
use App\Http\Resources\Admin\Catalog\TireProductResource;
use App\Models\Catalog\TireProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** CRUD шин. */
final readonly class TireProductController
{
    /**
     * Список шин.
     *
     * @group Шины
     */
    public function index(): AnonymousResourceCollection
    {
        return TireProductResource::collection(
            TireProduct::with('brand')->orderBy('id', 'desc')->paginate(50)
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
