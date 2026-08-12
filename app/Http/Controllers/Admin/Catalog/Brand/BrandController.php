<?php

namespace App\Http\Controllers\Admin\Catalog\Brand;

use App\Actions\Catalog\Brand\GetBrandList;
use App\Http\Requests\Admin\Catalog\Brand\BrandIndexRequest;
use App\Http\Requests\Admin\Catalog\Brand\BrandRequest;
use App\Http\Resources\Admin\Catalog\Brand\BrandResource;
use App\Models\Catalog\Brand\Brand;
use App\Preconditions\Catalog\EnsureBrandHasNoProducts;
use App\Services\Admin\FileService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** CRUD брендов.
 *
 */
#[Group('Каталог / бренды', weight: 25)]
final readonly class BrandController
{
    public function __construct(
        private GetBrandList $getBrandList,
        private EnsureBrandHasNoProducts $ensureBrandHasNoProducts,
        private FileService $fileService,
    ) {}

    /** Список брендов. */
    public function index(BrandIndexRequest $request): AnonymousResourceCollection
    {
        return BrandResource::collection(
            $this->getBrandList->execute($request->validated())
        );
    }

    /** Создать бренд. */
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

    /** Получить бренд. */
    public function show(int $id): BrandResource
    {
        $brand = Brand::withCount(['tireProducts', 'wheelProducts'])
            ->findOrFail($id);

        return new BrandResource($brand);
    }

    /** Обновить бренд. */
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

    /** Удалить бренд. */
    public function destroy(int $id): JsonResponse
    {
        $brand = Brand::withCount(['tireProducts', 'wheelProducts'])->findOrFail($id);
        $this->ensureBrandHasNoProducts->ensure($brand);

        $brand->delete();

        return response()->json(null, 204);
    }
}
