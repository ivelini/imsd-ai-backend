<?php

namespace App\Http\Controllers\Admin\Catalog\Wheel;

use App\Actions\Catalog\Wheel\GetWheelProductList;
use App\Http\Requests\Admin\Catalog\Wheel\WheelProductIndexRequest;
use App\Http\Requests\Admin\Catalog\Wheel\WheelProductRequest;
use App\Http\Requests\Admin\Catalog\Wheel\WheelProductShowRequest;
use App\Http\Resources\Admin\Catalog\Wheel\WheelProductResource;
use App\Models\Catalog\Wheel\WheelProduct;
use App\Services\Catalog\DeliveryInfoService;
use App\Services\Catalog\DisplayNameResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** CRUD дисков. */
final readonly class WheelProductController
{
    public function __construct(
        private GetWheelProductList $getWheelProductList,
        private DeliveryInfoService $deliveryInfo,
        private DisplayNameResolver $displayName,
    ) {}

    public function index(WheelProductIndexRequest $request): AnonymousResourceCollection
    {
        $products = $this->getWheelProductList->execute($request->validated());

        foreach ($products->items() as $product) {
            $this->deliveryInfo->enrichProduct($product, $request->validated('city_id'));
        }

        return WheelProductResource::collection($products);
    }

    public function show(WheelProductShowRequest $request, int $id): WheelProductResource
    {
        $wheel = WheelProduct::with('brand', 'model', 'images', 'stocks.warehouse.deliverySchedules')
            ->findOrFail($id);

        $this->deliveryInfo->enrichProduct($wheel, $request->validated('city_id'));

        return new WheelProductResource($wheel);
    }

    public function store(WheelProductRequest $request): JsonResponse
    {
        $data = $this->displayName->resolve($request->validated());
        $wheel = WheelProduct::create($data);

        return (new WheelProductResource($wheel->load('brand', 'model', 'images')))->response()->setStatusCode(201);
    }

    public function update(WheelProductRequest $request, int $id): WheelProductResource
    {
        $wheel = WheelProduct::findOrFail($id);
        $wheel->update($this->displayName->resolve($request->validated()));

        return new WheelProductResource($wheel->load('brand', 'model', 'images'));
    }

    public function destroy(int $id): JsonResponse
    {
        WheelProduct::findOrFail($id)->delete();

        return response()->json(null, 204);
    }
}
