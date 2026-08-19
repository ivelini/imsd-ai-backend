<?php

namespace App\Http\Controllers\Admin\Catalog\Wheel;

use App\Actions\Catalog\Wheel\GetWheelProductList;
use App\Http\Requests\Admin\Catalog\Wheel\WheelProductIndexRequest;
use App\Http\Requests\Admin\Catalog\Wheel\WheelProductRequest;
use App\Http\Requests\Admin\Catalog\Wheel\WheelProductShowRequest;
use App\Http\Resources\Admin\Catalog\Wheel\WheelProductResource;
use App\Models\Catalog\Wheel\WheelProduct;
use App\Services\Catalog\DisplayNameResolver;
use App\Services\Catalog\ProductSlugService;
use App\Services\Delivery\DeliveryInfoService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** CRUD дисков. */
#[Group('Каталог / диски', weight: 40)]
final readonly class WheelProductController
{
    public function __construct(
        private GetWheelProductList $getWheelProductList,
        private DeliveryInfoService $deliveryInfo,
        private DisplayNameResolver $displayName,
        private ProductSlugService $slugService,
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
        $data['slug'] = $this->slugFrom($data);

        $wheel = WheelProduct::create($data);

        return (new WheelProductResource($wheel->load('brand', 'model', 'images')))->response()->setStatusCode(201);
    }

    public function update(WheelProductRequest $request, int $id): WheelProductResource
    {
        $wheel = WheelProduct::findOrFail($id);

        $data = $this->displayName->resolve($request->validated());
        $data['slug'] = $this->slugFrom($data, $id);

        $wheel->update($data);

        return new WheelProductResource($wheel->load('brand', 'model', 'images'));
    }

    public function destroy(int $id): JsonResponse
    {
        WheelProduct::findOrFail($id)->delete();

        return response()->json(null, 204);
    }

    /** @param  array<string, mixed>  $data */
    private function slugFrom(array $data, ?int $ignoreId = null): string
    {
        return $this->slugService->wheel(
            brandId: (int) $data['brand_id'],
            name: $data['name'],
            width: $data['width'] ?? null,
            diameter: isset($data['diameter']) ? (int) $data['diameter'] : null,
            et: $data['et'] ?? null,
            pcd: $data['pcd'] ?? null,
            hubDiameter: $data['hub_diameter'] ?? null,
            ignoreId: $ignoreId,
        );
    }
}
