<?php

namespace App\DTOs\Booking;

use Carbon\CarbonImmutable;

/** Входные данные для Action GetSlotDays: диапазон календаря записи. */
final readonly class GetSlotDaysInput
{
    public function __construct(
        public CarbonImmutable $from,
        public CarbonImmutable $to,
    ) {}
}
