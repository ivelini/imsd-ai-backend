<?php

namespace App\Http\Controllers\Admin\Catalog;

use App\Http\Requests\Admin\Catalog\PromotionRequest;
use App\Http\Resources\Admin\Catalog\PromotionResource;
use App\Models\Catalog\Promotion;
use App\Services\Admin\PromotionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** CRUD акций. */
final readonly class PromotionController
{
    public function __construct(
        private PromotionService $promotionService,
    ) {}

    /**
     * @group Акции
     */
    public function index(): AnonymousResourceCollection
    {
        return PromotionResource::collection(
            Promotion::orderBy('starts_at', 'desc')->paginate(50)
        );
    }

    /**
     * @group Акции
     */
    public function store(PromotionRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data = $this->resolvePromotable($data);

        $promotion = Promotion::create($data);

        return (new PromotionResource($promotion))->response()->setStatusCode(201);
    }

    /**
     * @group Акции
     */
    public function show(int $id): PromotionResource
    {
        return new PromotionResource(Promotion::findOrFail($id));
    }

    /**
     * @group Акции
     */
    public function update(PromotionRequest $request, int $id): PromotionResource
    {
        $data = $request->validated();
        $data = $this->resolvePromotable($data);

        $promotion = Promotion::findOrFail($id);
        $promotion->update($data);

        return new PromotionResource($promotion);
    }

    /**
     * @group Акции
     */
    public function destroy(int $id): JsonResponse
    {
        Promotion::findOrFail($id)->delete();

        return response()->json(null, 204);
    }

    /** Преобразовать тип привязки в morph-тип. */
    private function resolvePromotable(array $data): array
    {
        if (! isset($data['promotable_type'])) {
            return $data;
        }

        $data['promotable_type'] = $this->promotionService->resolvePromotableType($data['promotable_type']);

        return $data;
    }
}
