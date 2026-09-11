<?php

namespace App\Http\Controllers\Booking;

use App\Actions\Booking\GetDaySlots;
use Carbon\CarbonImmutable;
use Dedoc\Scramble\Attributes\Group;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Сетка часов дня записи: слоты от первого доступного часа, закрытые помечены. */
#[Group('Запись на шиномонтаж', weight: 20)]
final readonly class GetDaySlotsController
{
    public function __construct(private GetDaySlots $getDaySlots) {}

    public function __invoke(Request $request, string $date): JsonResponse
    {
        // Формат пути уже отфильтрован where-регэкспом маршрута; здесь — реальное существование даты
        if (! CarbonImmutable::hasFormat($date, 'Y-m-d')) {
            throw new DomainException('Невалидная дата', 422);
        }

        $slots = $this->getDaySlots->execute(CarbonImmutable::parse($date));

        return response()->json(['data' => ['slots' => $slots]]);
    }
}
