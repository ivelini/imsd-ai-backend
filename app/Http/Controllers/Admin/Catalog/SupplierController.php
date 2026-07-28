<?php

namespace App\Http\Controllers\Admin\Catalog;

use App\Http\Requests\Admin\Catalog\SupplierIndexRequest;
use App\Http\Requests\Admin\Catalog\SupplierRequest;
use App\Http\Resources\Admin\Catalog\SupplierResource;
use App\Models\Catalog\Supplier;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** CRUD поставщиков.
 *
 * @group Поставщики
 */
final readonly class SupplierController
{
    /** Поля, доступные для сортировки в списке. */
    private const array ALLOWED_SORT = ['id', 'name', 'code', 'created_at'];

    public function index(SupplierIndexRequest $request): AnonymousResourceCollection
    {
        $data = $request->validated();
        $perPage = min(max((int) ($data['per_page'] ?? 50), 10), 100);

        $query = Supplier::query();

        if (! empty($data['search'])) {
            $q = '%'.$data['search'].'%';
            $query->where(function ($qry) use ($q) {
                $qry->where('name', 'like', $q)->orWhere('code', 'like', $q);
            });
        }

        $sortBy = in_array($data['sort_by'] ?? 'name', self::ALLOWED_SORT, true) ? $data['sort_by'] : 'name';
        $sortDir = ($data['sort_dir'] ?? 'asc') === 'asc' ? 'asc' : 'desc';

        return SupplierResource::collection(
            $query->orderBy($sortBy, $sortDir)->paginate($perPage)
        );
    }

    public function store(SupplierRequest $request): JsonResponse
    {
        $supplier = Supplier::create($request->validated());

        return (new SupplierResource($supplier))->response()->setStatusCode(201);
    }

    public function show(int $id): SupplierResource
    {
        return new SupplierResource(Supplier::findOrFail($id));
    }

    public function update(SupplierRequest $request, int $id): SupplierResource
    {
        $supplier = Supplier::findOrFail($id);
        $supplier->update($request->validated());

        return new SupplierResource($supplier);
    }

    public function destroy(int $id): JsonResponse
    {
        $supplier = Supplier::withCount('tireProducts')->findOrFail($id);

        if ($supplier->tire_products_count > 0) {
            throw new DomainException(
                "Невозможно удалить поставщика «{$supplier->name}»: {$supplier->tire_products_count} товаров ссылается на него."
            );
        }

        $supplier->delete();

        return response()->json(null, 204);
    }
}
