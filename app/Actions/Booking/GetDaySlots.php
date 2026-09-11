<?php

namespace App\Actions\Booking;

use App\Services\Booking\SlotAvailabilityReader;
use Carbon\CarbonImmutable;

/** Сетка часов дня записи от первого доступного часа. */
final readonly class GetDaySlots
{
    public function __construct(private SlotAvailabilityReader $slots) {}

    /** @return list<array{hour: int, is_closed: bool}> */
    public function execute(CarbonImmutable $date): array
    {
        return $this->slots->daySlots($date);
    }
}
