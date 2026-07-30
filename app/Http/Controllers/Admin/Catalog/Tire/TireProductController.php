<?php

namespace App\Http\Controllers\Admin\Catalog\Tire;

use App\Actions\Catalog\EnsureProductDisplayName;
use App\Actions\Catalog\GetTireProductList;
use App\Http\Requests\Admin\Catalog\Tire\TireProductIndexRequest;
use App\Http\Requests\Admin\Catalog\Tire\TireProductRequest;
use App\Http\Resources\Admin\Catalog\Tire\TireProductResource;
use App\Models\Catalog\TireProduct;
use App\Services\Catalog\DeliveryInfoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** CRUD шин. */
final readonly class TireProductController
{
    public function __construct(
        private GetTireProductList $getTireProductList,
        private DeliveryInfoService $deliveryInfo,
        private EnsureProductDisplayName $ensureDisplayName,
    ) {}

    /**
     * Список шин.
     *
     * @group Шины
     */
    public function index(TireProductIndexRequest $request): AnonymousResourceCollection
    {
        $products = $this->getTireProductList->execute($request->validated());

        foreach ($products->items() as $product) {
            $this->deliveryInfo->enrichProduct($product, $request->validated('city_id'));
        }

        return TireProductResource::collection($products);
    }

    /**
     * Получить шину.
     *
     * @group Шины
     */
    public function show(int $id): TireProductResource
    {
        $tire = TireProduct::with('brand', 'model', 'images', 'stocks.warehouse.deliverySchedules')
            ->findOrFail($id);

        $cityId = request()->get('city_id');
        $this->deliveryInfo->enrichProduct($tire, $cityId ? (int) $cityId : null);

        return new TireProductResource($tire);
    }

    /**
     * Создать шину.
     *
     * @group Шины
     */
    public function store(TireProductRequest $request): JsonResponse
    {
        $data = $this->ensureDisplayName->execute($request->validated());
        $tire = TireProduct::create($data);

        return (new TireProductResource($tire->load('brand', 'model', 'images')))->response()->setStatusCode(201);
    }

    /**
     * Обновить шину.
     *
     * @group Шины
     */
    public function update(TireProductRequest $request, int $id): TireProductResource
    {
        $tire = TireProduct::findOrFail($id);
        $tire->update($this->ensureDisplayName->execute($request->validated()));

        return new TireProductResource($tire->load('brand', 'model', 'images'));
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
