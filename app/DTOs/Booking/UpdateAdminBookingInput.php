<?php

namespace App\DTOs\Booking;

use App\Enums\Booking\BookingStatus;
use App\Enums\Booking\CarType;
use App\Models\Booking\Booking;

/** Данные правки записи оператором (ФТ-19): снимок полей и состав в новом виде. */
final readonly class UpdateAdminBookingInput
{
    /**
     * @param  string  $startTime  время начала «HH:MM:00» внутри часа слота
     * @param  list<BookingItemInput>  $items  полный состав записи: услуги, которых в нём нет, удаляются
     */
    public function __construct(
        public Booking $booking,
        public ?string $plate,
        public int $radius,
        public CarType $carType,
        public BookingStatus $status,
        public ?string $cancelReason,
        public string $startTime,
        public array $items,
    ) {}
}
