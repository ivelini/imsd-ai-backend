<?php

namespace App\Http\Controllers\Admin\Catalog\Wheel;

use App\Actions\Catalog\EnsureProductDisplayName;
use App\Actions\Catalog\GetWheelProductList;
use App\Http\Requests\Admin\Catalog\Wheel\WheelProductIndexRequest;
use App\Http\Requests\Admin\Catalog\Wheel\WheelProductRequest;
use App\Http\Resources\Admin\Catalog\Wheel\WheelProductResource;
use App\Models\Catalog\WheelProduct;
use App\Services\Cache\Catalog\ProductCacheService;
use App\Services\Catalog\DeliveryInfoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** CRUD дисков. */
final readonly class WheelProductController
{
    public function __construct(
        private GetWheelProductList $getWheelProductList,
        private ProductCacheService $cache,
        private DeliveryInfoService $deliveryInfo,
        private EnsureProductDisplayName $ensureDisplayName,
    ) {}

    /**
     * @group Диски
     */
    public function index(WheelProductIndexRequest $request): AnonymousResourceCollection
    {
        $products = $this->getWheelProductList->execute($request->validated());

        foreach ($products->items() as $product) {
            $this->deliveryInfo->enrichProduct($product, $request->validated('city_id'));
        }

        return WheelProductResource::collection($products);
    }

    /**
     * @group Диски
     */
    public function show(int $id): WheelProductResource
    {
        $wheel = $this->cache->rememberWheel($id, fn () => WheelProduct::with('brand', 'model', 'stocks.warehouse.deliverySchedules')->findOrFail($id)
        );

        $cityId = request()->get('city_id');
        $this->deliveryInfo->enrichProduct($wheel, $cityId ? (int) $cityId : null);

        return new WheelProductResource($wheel);
    }

    /**
     * @group Диски
     */
    public function store(WheelProductRequest $request): JsonResponse
    {
        $data = $this->ensureDisplayName->execute($request->validated());
        $wheel = WheelProduct::create($data);

        return (new WheelProductResource($wheel->load('brand', 'model')))->response()->setStatusCode(201);
    }

    /**
     * @group Диски
     */
    public function update(WheelProductRequest $request, int $id): WheelProductResource
    {
        $wheel = WheelProduct::findOrFail($id);
        $wheel->update($this->ensureDisplayName->execute($request->validated()));

        return new WheelProductResource($wheel->load('brand', 'model'));
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
