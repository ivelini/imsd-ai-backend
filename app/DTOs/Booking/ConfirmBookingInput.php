<?php

namespace App\DTOs\Booking;

use App\Enums\Booking\CarType;
use App\Models\Booking\BookingCode;
use Carbon\CarbonImmutable;

/** Данные создания записи при подтверждении кода (ФТ-8): слот, параметры, состав. */
final readonly class ConfirmBookingInput
{
    /**
     * @param  array<int, int>  $quantities  service_id => количество 1–4
     * @param  bool  $closeSlot  закрыть часовой слот с привязкой к записи: бронь с сайта — всегда, запись из админки — по чекбоксу
     */
    public function __construct(
        public BookingCode $code,
        public string $name,
        public string $phone,
        public ?string $plate,
        public CarbonImmutable $date,
        public int $hour,
        public int $radius,
        public CarType $carType,
        public array $quantities,
        public bool $closeSlot = false,
    ) {}
}
