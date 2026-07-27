<?php

namespace App\Http\Controllers\Admin\Catalog;

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
    public function index(): AnonymousResourceCollection
    {
        return SupplierResource::collection(
            Supplier::orderBy('name')->paginate(50)
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
