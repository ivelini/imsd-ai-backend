<?php

namespace App\Preconditions\Booking;

use App\Services\Booking\SlotAvailabilityReader;
use Carbon\CarbonImmutable;
use DomainException;

/**
 * Быстрая проверка выбираемости слота до транзакции (UX-ошибка раньше).
 * От гонки не защищает: финальная проверка — lockForUpdate в ConfirmBooking.
 */
final readonly class EnsureSlotSelectable
{
    public function __construct(private SlotAvailabilityReader $slots) {}

    public function ensure(CarbonImmutable $date, int $hour): void
    {
        if (! $this->slots->isSelectableHour($date, $hour)) {
            throw new DomainException('Слот недоступен для записи', 409);
        }
    }
}
