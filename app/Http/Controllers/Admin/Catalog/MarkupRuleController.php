<?php

namespace App\Http\Controllers\Admin\Catalog;

use App\Http\Requests\Admin\Catalog\MarkupRuleRequest;
use App\Http\Resources\Admin\Catalog\MarkupRuleResource;
use App\Models\Catalog\WarehouseMarkupRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** CRUD правил наценки складов. */
final readonly class MarkupRuleController
{
    /**
     * @group Правила наценки
     */
    public function index(): AnonymousResourceCollection
    {
        return MarkupRuleResource::collection(
            WarehouseMarkupRule::with('warehouse')->orderBy('warehouse_id')->paginate(50)
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
