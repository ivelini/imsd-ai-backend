<?php

namespace App\Http\Controllers\Admin\Catalog;

use App\Http\Requests\Admin\Catalog\BrandIndexRequest;
use App\Http\Requests\Admin\Catalog\BrandRequest;
use App\Http\Resources\Admin\Catalog\BrandResource;
use App\Models\Catalog\Brand;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** CRUD брендов.
 *
 * @group Бренды
 */
final readonly class BrandController
{
    /** Поля, доступные для сортировки в списке. */
    private const array ALLOWED_SORT = ['id', 'name', 'type', 'created_at'];

    /**
     * Список брендов.
     *
     * @authenticated
     */
    public function index(BrandIndexRequest $request): AnonymousResourceCollection
    {
        $data = $request->validated();
        $perPage = min(max((int) ($data['per_page'] ?? 50), 10), 100);

        $query = Brand::withCount(['tireProducts', 'wheelProducts']);

        if (! empty($data['search'])) {
            $query->where('name', 'like', '%'.$data['search'].'%');
        }

        if (! empty($data['type'])) {
            $query->where('type', $data['type']);
        }

        $sortBy = in_array($data['sort_by'] ?? 'name', self::ALLOWED_SORT, true) ? $data['sort_by'] : 'name';
        $sortDir = ($data['sort_dir'] ?? 'asc') === 'asc' ? 'asc' : 'desc';

        return BrandResource::collection(
            $query->orderBy($sortBy, $sortDir)->paginate($perPage)
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

        $productsCount = ($brand->tire_products_count ?? 0) + ($brand->wheel_products_count ?? 0);

        if ($productsCount > 0) {
            throw new DomainException(
                "Невозможно удалить бренд «{$brand->name}»: {$productsCount} товаров использует его."
            );
        }

        $brand->delete();

        return response()->json(null, 204);
    }
}
