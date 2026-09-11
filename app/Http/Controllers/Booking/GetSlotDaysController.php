<?php

namespace App\Http\Controllers\Booking;

use App\Actions\Booking\GetSlotDays;
use App\DTOs\Booking\GetSlotDaysInput;
use App\Http\Requests\Booking\GetSlotDaysRequest;
use Carbon\CarbonImmutable;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

/** Карта доступности дней календаря записи: «Y-m-d» => есть ли открытый слот. */
#[Group('Запись на шиномонтаж', weight: 20)]
final readonly class GetSlotDaysController
{
    public function __construct(private GetSlotDays $getSlotDays) {}

    public function __invoke(GetSlotDaysRequest $request): JsonResponse
    {
        $days = $this->getSlotDays->execute(new GetSlotDaysInput(
            CarbonImmutable::parse($request->validated('date_from')),
            CarbonImmutable::parse($request->validated('date_to')),
        ));

        return response()->json(['data' => ['days' => $days]]);
    }
}
