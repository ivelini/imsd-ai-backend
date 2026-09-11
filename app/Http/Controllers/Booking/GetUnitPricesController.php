<?php

namespace App\Http\Controllers\Booking;

use App\Actions\Booking\GetUnitPrices;
use App\Enums\Booking\CarType;
use App\Http\Requests\Booking\GetUnitPricesRequest;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

/** Цены за единицу по каталогу при выбранных параметрах авто (карточки услуг). */
#[Group('Запись на шиномонтаж', weight: 20)]
final readonly class GetUnitPricesController
{
    public function __construct(private GetUnitPrices $getUnitPrices) {}

    public function __invoke(GetUnitPricesRequest $request): JsonResponse
    {
        $unitPrices = $this->getUnitPrices->execute(
            (int) $request->validated('radius'),
            CarType::from($request->validated('car_type')),
        );

        return response()->json(['data' => ['unit_prices' => $unitPrices]]);
    }
}
