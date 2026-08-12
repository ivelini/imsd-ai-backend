<?php

namespace App\Http\Controllers\Admin\Catalog\Tire;

use App\Actions\Catalog\Tire\GetTireProductList;
use App\Http\Requests\Admin\Catalog\Tire\TireProductIndexRequest;
use App\Http\Requests\Admin\Catalog\Tire\TireProductRequest;
use App\Http\Requests\Admin\Catalog\Tire\TireProductShowRequest;
use App\Http\Resources\Admin\Catalog\Tire\TireProductResource;
use App\Models\Catalog\Tire\TireProduct;
use App\Services\Catalog\DeliveryInfoService;
use App\Services\Catalog\DisplayNameResolver;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * CRUD шин.
 *
 * Управление товарами шин: список с фильтрацией, создание, просмотр, обновление, удаление.
 */
#[Group('Каталог / шины', weight: 30)]
final readonly class TireProductController
{
    public function __construct(
        private GetTireProductList $getTireProductList,
        private DeliveryInfoService $deliveryInfo,
        private DisplayNameResolver $displayName,
    ) {}

    /** Список шин. */
    public function index(TireProductIndexRequest $request): AnonymousResourceCollection
    {
        $products = $this->getTireProductList->execute($request->validated());

        foreach ($products->items() as $product) {
            $this->deliveryInfo->enrichProduct($product, $request->validated('city_id'));
        }

        return TireProductResource::collection($products);
    }

    /** Получить шину. */
    public function show(TireProductShowRequest $request, int $id): TireProductResource
    {
        $tire = TireProduct::with('brand', 'model', 'images', 'stocks.warehouse.deliverySchedules')
            ->findOrFail($id);

        $this->deliveryInfo->enrichProduct($tire, $request->validated('city_id'));

        return new TireProductResource($tire);
    }

    /** Создать шину. */
    public function store(TireProductRequest $request): JsonResponse
    {
        $data = $this->displayName->resolve($request->validated());
        $tire = TireProduct::create($data);

        return (new TireProductResource($tire->load('brand', 'model', 'images')))->response()->setStatusCode(201);
    }

    /** Обновить шину. */
    public function update(TireProductRequest $request, int $id): TireProductResource
    {
        $tire = TireProduct::findOrFail($id);
        $tire->update($this->displayName->resolve($request->validated()));

        return new TireProductResource($tire->load('brand', 'model', 'images'));
    }

    /** Удалить шину. */
    public function destroy(int $id): JsonResponse
    {
        TireProduct::findOrFail($id)->delete();

        return response()->json(null, 204);
    }
}
