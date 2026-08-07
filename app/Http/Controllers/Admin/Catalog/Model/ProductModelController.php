<?php

namespace App\Http\Controllers\Admin\Catalog\Model;

use App\Actions\Catalog\Model\GetProductModelList;
use App\Http\Requests\Admin\Catalog\Model\ProductModelIndexRequest;
use App\Http\Requests\Admin\Catalog\Model\ProductModelRequest;
use App\Http\Resources\Admin\Catalog\Model\ProductModelResource;
use App\Models\Catalog\Model\ProductModel;
use App\Preconditions\Catalog\EnsureModelHasNoProducts;
use App\Services\Admin\FileService;
use App\Services\Cache\Catalog\ReferencesCacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** CRUD моделей товаров.
 *
 * @group Модели
 */
final readonly class ProductModelController
{
    public function __construct(
        private GetProductModelList $getProductModelList,
        private EnsureModelHasNoProducts $ensureModelHasNoProducts,
        private ReferencesCacheService $referencesCache,
        private FileService $fileService,
    ) {}

    /**
     * Список моделей.
     *
     * @authenticated
     */
    public function index(ProductModelIndexRequest $request): AnonymousResourceCollection
    {
        return ProductModelResource::collection(
            $this->getProductModelList->execute($request->validated())
        );
    }

    /**
     * Создать модель.
     *
     * @authenticated
     */
    public function store(ProductModelRequest $request): JsonResponse
    {
        $data = $request->validated();
        unset($data['image']);

        if ($request->hasFile('image')) {
            $data['image'] = $this->fileService->store($request->file('image'), 'models');
        }

        $model = ProductModel::create($data);
        $this->referencesCache->forget();

        return (new ProductModelResource($model))->response()->setStatusCode(201);
    }

    /**
     * Получить модель.
     *
     * @authenticated
     */
    public function show(int $id): ProductModelResource
    {
        return new ProductModelResource(
            ProductModel::with('brand')
                ->withCount(['tireProducts', 'wheelProducts'])
                ->findOrFail($id)
        );
    }

    /**
     * Обновить модель.
     *
     * @authenticated
     */
    public function update(ProductModelRequest $request, int $id): ProductModelResource
    {
        $model = ProductModel::findOrFail($id);
        $data = $request->validated();
        unset($data['image']);

        if ($request->hasFile('image')) {
            $data['image'] = $this->fileService->replace($model->image, $request->file('image'), 'models');
        }

        $model->update($data);
        $this->referencesCache->forget();

        return new ProductModelResource($model);
    }

    /**
     * Удалить модель.
     *
     * @authenticated
     *
     * @response status=204
     * @response status=409 {"message": "Невозможно удалить модель «A503»: 12 товаров использует её."}
     */
    public function destroy(int $id): JsonResponse
    {
        $model = ProductModel::withCount(['tireProducts', 'wheelProducts'])->findOrFail($id);
        $this->ensureModelHasNoProducts->ensure($model);

        $model->delete();
        $this->referencesCache->forget();

        return response()->json(null, 204);
    }
}
