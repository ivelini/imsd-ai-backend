<?php

namespace App\Http\Controllers\Admin\Catalog\Supplier;

use App\Actions\Catalog\Supplier\GetSupplierList;
use App\Http\Requests\Admin\Catalog\Supplier\SupplierIndexRequest;
use App\Http\Requests\Admin\Catalog\Supplier\SupplierRequest;
use App\Http\Resources\Admin\Catalog\Supplier\SupplierResource;
use App\Models\Catalog\Supplier\Supplier;
use App\Preconditions\Catalog\EnsureSupplierHasNoProducts;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** CRUD поставщиков.
 *
 */
#[Group('Каталог / поставщики', weight: 25)]
final readonly class SupplierController
{
    public function __construct(
        private GetSupplierList $getSupplierList,
        private EnsureSupplierHasNoProducts $ensureSupplierHasNoProducts,
    ) {}

    public function index(SupplierIndexRequest $request): AnonymousResourceCollection
    {
        return SupplierResource::collection(
            $this->getSupplierList->execute($request->validated())
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
        $supplier = Supplier::withCount(['tireProducts', 'wheelProducts'])->findOrFail($id);
        $this->ensureSupplierHasNoProducts->ensure($supplier);

        $supplier->delete();

        return response()->json(null, 204);
    }
}
