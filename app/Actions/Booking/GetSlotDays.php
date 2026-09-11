<?php

namespace App\Actions\Booking;

use App\DTOs\Booking\GetSlotDaysInput;
use App\Services\Booking\SlotAvailabilityReader;

/** Карта доступности дней календаря записи (Y-m-d => есть ли открытый слот). */
final readonly class GetSlotDays
{
    public function __construct(private SlotAvailabilityReader $slots) {}

    /** @return array<string, bool> */
    public function execute(GetSlotDaysInput $input): array
    {
        return $this->slots->daysWithAvailability($input->from, $input->to);
    }
}
