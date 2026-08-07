<?php

namespace App\Http\Controllers\Admin\Catalog\Brand;

use App\Actions\Catalog\Brand\GetBrandList;
use App\Http\Requests\Admin\Catalog\Brand\BrandIndexRequest;
use App\Http\Requests\Admin\Catalog\Brand\BrandRequest;
use App\Http\Resources\Admin\Catalog\Brand\BrandResource;
use App\Models\Catalog\Brand\Brand;
use App\Preconditions\Catalog\EnsureBrandHasNoProducts;
use App\Services\Admin\FileService;
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
        private FileService $fileService,
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
        $data = $request->validated();
        unset($data['logo']);

        if ($request->hasFile('logo')) {
            $data['logo'] = $this->fileService->store($request->file('logo'), 'brands');
        }

        $brand = Brand::create($data);

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
        $data = $request->validated();
        unset($data['logo']);

        if ($request->hasFile('logo')) {
            $data['logo'] = $this->fileService->replace($brand->logo, $request->file('logo'), 'brands');
        }

        $brand->update($data);

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
