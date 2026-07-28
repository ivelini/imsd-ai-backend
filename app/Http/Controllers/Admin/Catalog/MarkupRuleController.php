<?php

namespace App\Http\Controllers\Admin\Catalog;

use App\Http\Requests\Admin\Catalog\MarkupRuleIndexRequest;
use App\Http\Requests\Admin\Catalog\MarkupRuleRequest;
use App\Http\Resources\Admin\Catalog\MarkupRuleResource;
use App\Models\Catalog\WarehouseMarkupRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** CRUD правил наценки складов. */
final readonly class MarkupRuleController
{
    /** Поля, доступные для сортировки в списке. */
    private const array ALLOWED_SORT = ['id', 'warehouse_id', 'price_from', 'price_to', 'coefficient', 'created_at'];

    /**
     * @group Правила наценки
     */
    public function index(MarkupRuleIndexRequest $request): AnonymousResourceCollection
    {
        $data = $request->validated();
        $perPage = min(max((int) ($data['per_page'] ?? 50), 10), 100);

        $query = WarehouseMarkupRule::with('warehouse');

        if (! empty($data['warehouse_id'])) {
            $query->where('warehouse_id', (int) $data['warehouse_id']);
        }

        $sortBy = in_array($data['sort_by'] ?? 'warehouse_id', self::ALLOWED_SORT, true) ? $data['sort_by'] : 'warehouse_id';
        $sortDir = ($data['sort_dir'] ?? 'asc') === 'asc' ? 'asc' : 'desc';

        return MarkupRuleResource::collection(
            $query->orderBy($sortBy, $sortDir)->paginate($perPage)
        );
    }

    /**
     * @group Правила наценки
     */
    public function store(MarkupRuleRequest $request): JsonResponse
    {
        $rule = WarehouseMarkupRule::create($request->validated());

        return (new MarkupRuleResource($rule))->response()->setStatusCode(201);
    }

    /**
     * @group Правила наценки
     */
    public function show(int $id): MarkupRuleResource
    {
        return new MarkupRuleResource(
            WarehouseMarkupRule::with('warehouse')->findOrFail($id)
        );
    }

    /**
     * @group Правила наценки
     */
    public function update(MarkupRuleRequest $request, int $id): MarkupRuleResource
    {
        $rule = WarehouseMarkupRule::findOrFail($id);
        $rule->update($request->validated());

        return new MarkupRuleResource($rule->load('warehouse'));
    }

    /**
     * @group Правила наценки
     */
    public function destroy(int $id): JsonResponse
    {
        WarehouseMarkupRule::findOrFail($id)->delete();

        return response()->json(null, 204);
    }
}
