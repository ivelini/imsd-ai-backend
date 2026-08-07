<?php

namespace App\Http\Controllers\Admin\Catalog\MarkupRule;

use App\Actions\Catalog\MarkupRule\GetMarkupRuleList;
use App\Http\Requests\Admin\Catalog\MarkupRule\MarkupRuleIndexRequest;
use App\Http\Requests\Admin\Catalog\MarkupRule\MarkupRuleRequest;
use App\Http\Resources\Admin\Catalog\MarkupRule\MarkupRuleResource;
use App\Models\Catalog\MarkupRule\WarehouseMarkupRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** CRUD правил наценки складов. */
final readonly class MarkupRuleController
{
    public function __construct(
        private GetMarkupRuleList $getMarkupRuleList,
    ) {}

    public function index(MarkupRuleIndexRequest $request): AnonymousResourceCollection
    {
        return MarkupRuleResource::collection(
            $this->getMarkupRuleList->execute($request->validated())
        );
    }

    public function store(MarkupRuleRequest $request): JsonResponse
    {
        $rule = WarehouseMarkupRule::create($request->validated());

        return (new MarkupRuleResource($rule))->response()->setStatusCode(201);
    }

    public function show(int $id): MarkupRuleResource
    {
        return new MarkupRuleResource(
            WarehouseMarkupRule::with('warehouse')->findOrFail($id)
        );
    }

    public function update(MarkupRuleRequest $request, int $id): MarkupRuleResource
    {
        $rule = WarehouseMarkupRule::findOrFail($id);
        $rule->update($request->validated());

        return new MarkupRuleResource($rule->load('warehouse'));
    }

    public function destroy(int $id): JsonResponse
    {
        WarehouseMarkupRule::findOrFail($id)->delete();

        return response()->json(null, 204);
    }
}
