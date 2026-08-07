<?php

namespace App\Http\Controllers\Admin\Catalog\Wheel;

use App\Actions\Catalog\Wheel\GetWheelDimensions;
use App\Http\Requests\Admin\Catalog\Wheel\WheelDimensionsRequest;
use Illuminate\Http\JsonResponse;

/** Доступные значения фильтров дисков. */
final readonly class GetWheelDimensionsController
{
    public function __construct(
        private GetWheelDimensions $getWheelDimensions,
    ) {}

    /** Получить доступные значения фильтров дисков. */
    public function __invoke(WheelDimensionsRequest $request): JsonResponse
    {
        return response()->json([
            'data' => $this->getWheelDimensions->execute($request->validated()),
        ]);
    }
}
