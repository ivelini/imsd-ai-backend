<?php

namespace App\Http\Controllers\Admin\Catalog;

use App\Actions\Catalog\GetBrandList;
use App\Http\Requests\Admin\Catalog\BrandIndexRequest;
use App\Http\Requests\Admin\Catalog\BrandRequest;
use App\Http\Resources\Admin\Catalog\BrandResource;
use App\Models\Catalog\Brand;
use App\Preconditions\Catalog\EnsureBrandHasNoProducts;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** CRUD брендов.
 *
 * @group Бренды
 */
final readonly class BrandController
{
    public function __construct(
        private GetBrandList $getBrandList,
        private EnsureBrandHasNoProducts $ensureBrandHasNoProducts,
    ) {}

    /**
     * Список брендов.
     *
     * @authenticated
     */
    public function index(BrandIndexRequest $request): AnonymousResourceCollection
    {
        return BrandResource::collection(
            $this->getBrandList->execute($request->validated())
        );
    }

    /**
     * Создать бренд.
     *
     * @authenticated
     */
    public function store(BrandRequest $request): JsonResponse
    {
        $brand = Brand::create($request->validated());

        return (new BrandResource($brand))->response()->setStatusCode(201);
    }

    /**
     * Получить бренд.
     *
     * @authenticated
     */
    public function show(int $id): BrandResource
    {
        $brand = Brand::withCount(['tireProducts', 'wheelProducts'])
            ->findOrFail($id);

        return new BrandResource($brand);
    }

    /**
     * Обновить бренд.
     *
     * @authenticated
     */
    public function update(BrandRequest $request, int $id): BrandResource
    {
        $brand = Brand::findOrFail($id);
        $brand->update($request->validated());

        return new BrandResource($brand);
    }

    /**
     * Удалить бренд.
     *
     * @authenticated
     *
     * @response status=204
     * @response status=409 {"message": "Невозможно удалить бренд: 5 товаров использует его."}
     */
    public function destroy(int $id): JsonResponse
    {
        $brand = Brand::withCount(['tireProducts', 'wheelProducts'])->findOrFail($id);
        $this->ensureBrandHasNoProducts->ensure($brand);

        $brand->delete();

        return response()->json(null, 204);
    }
}
