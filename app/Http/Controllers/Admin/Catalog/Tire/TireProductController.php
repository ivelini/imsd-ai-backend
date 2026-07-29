<?php

namespace App\Http\Controllers\Admin\Catalog\Tire;

use App\Actions\Catalog\GetTireProductList;
use App\Http\Requests\Admin\Catalog\Tire\TireProductIndexRequest;
use App\Http\Requests\Admin\Catalog\Tire\TireProductRequest;
use App\Http\Resources\Admin\Catalog\Tire\TireProductResource;
use App\Models\Catalog\TireProduct;
use App\Services\Cache\Catalog\ProductCacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** CRUD шин. */
final readonly class TireProductController
{
    public function __construct(
        private GetTireProductList $getTireProductList,
        private ProductCacheService $cache,
    ) {}

    /**
     * Список шин.
     *
     * @group Шины
     */
    public function index(TireProductIndexRequest $request): AnonymousResourceCollection
    {
        return TireProductResource::collection(
            $this->getTireProductList->execute($request->validated())
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
            $this->cache->rememberTire($id, fn () => TireProduct::with('brand', 'stocks.warehouse')->findOrFail($id)
            )
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
