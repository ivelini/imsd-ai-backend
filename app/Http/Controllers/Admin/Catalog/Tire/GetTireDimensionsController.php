<?php

namespace App\Http\Controllers\Admin\Catalog\Tire;

use App\Actions\Catalog\Tire\GetTireDimensions;
use App\Http\Requests\Admin\Catalog\Tire\TireDimensionsRequest;
use Illuminate\Http\JsonResponse;

/** Доступные значения фильтров шин. */
final readonly class GetTireDimensionsController
{
    public function __construct(
        private GetTireDimensions $getTireDimensions,
    ) {}

    /**
     * Получить доступные значения фильтров шин.
     *
     * @authenticated
     *
     * @group Каталог — шины
     */
    public function __invoke(TireDimensionsRequest $request): JsonResponse
    {
        return response()->json([
            'data' => $this->getTireDimensions->execute($request->validated()),
        ]);
    }
}
