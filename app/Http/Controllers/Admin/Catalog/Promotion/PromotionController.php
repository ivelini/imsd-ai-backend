<?php

namespace App\Http\Controllers\Admin\Catalog\Promotion;

use App\Actions\Promotion\CreatePromotion;
use App\Actions\Promotion\GetPromotionList;
use App\Actions\Promotion\UpdatePromotion;
use App\Http\Requests\Admin\Catalog\Promotion\PromotionIndexRequest;
use App\Http\Requests\Admin\Catalog\Promotion\PromotionRequest;
use App\Http\Resources\Admin\Catalog\Promotion\PromotionResource;
use App\Models\Catalog\Promotion\Promotion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** CRUD акций. */
final readonly class PromotionController
{
    public function __construct(
        private GetPromotionList $getPromotionList,
        private CreatePromotion $createPromotion,
        private UpdatePromotion $updatePromotion,
    ) {}

    /**
     * @group Акции
     */
    public function index(PromotionIndexRequest $request): AnonymousResourceCollection
    {
        return PromotionResource::collection(
            $this->getPromotionList->execute($request->validated())
        );
    }

    /**
     * @group Акции
     */
    public function store(PromotionRequest $request): JsonResponse
    {
        $promotion = $this->createPromotion->execute($request->validated());

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
        $promotion = $this->updatePromotion->execute($id, $request->validated());

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
}
