<?php

namespace App\Http\Controllers\Admin\Geo;

use App\Actions\Geo\GetCityPriceRuleList;
use App\Http\Requests\Admin\Geo\CityPriceRuleIndexRequest;
use App\Http\Requests\Admin\Geo\CityPriceRuleRequest;
use App\Http\Resources\Admin\Geo\CityPriceRuleResource;
use App\Models\Delivery\CityPriceRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** CRUD правил наценки по городам. */
final readonly class CityPriceRuleController
{
    public function __construct(
        private GetCityPriceRuleList $getCityPriceRuleList,
    ) {}

    /**
     * @group Правила наценки городов
     */
    public function index(CityPriceRuleIndexRequest $request): AnonymousResourceCollection
    {
        return CityPriceRuleResource::collection(
            $this->getCityPriceRuleList->execute($request->validated())
        );
    }

    /**
     * @group Правила наценки городов
     */
    public function store(CityPriceRuleRequest $request): JsonResponse
    {
        $rule = CityPriceRule::create($request->validated());

        return (new CityPriceRuleResource($rule))->response()->setStatusCode(201);
    }

    /**
     * @group Правила наценки городов
     */
    public function show(int $id): CityPriceRuleResource
    {
        return new CityPriceRuleResource(
            CityPriceRule::with('city')->findOrFail($id)
        );
    }

    /**
     * @group Правила наценки городов
     */
    public function update(CityPriceRuleRequest $request, int $id): CityPriceRuleResource
    {
        $rule = CityPriceRule::findOrFail($id);
        $rule->update($request->validated());

        return new CityPriceRuleResource($rule->load('city'));
    }

    /**
     * @group Правила наценки городов
     */
    public function destroy(int $id): JsonResponse
    {
        CityPriceRule::findOrFail($id)->delete();

        return response()->json(null, 204);
    }
}
