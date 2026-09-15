<?php

namespace App\DTOs\Booking;

use App\Enums\Booking\CarType;
use App\Models\Auth\Admin;

/** Данные создания записи оператором из панели (ФТ-18). */
final readonly class CreateAdminBookingInput
{
    /**
     * @param  list<BookingItemInput>  $items  состав: услуга, количество и цена за единицу (правит оператор)
     * @param  string  $startTime  время начала «HH:MM:00» внутри часа слота: ровно на начало часа — час занимается
     */
    public function __construct(
        public Admin $operator,
        public string $phone,
        public string $name,
        public string $surname,
        public ?string $patronymic,
        public ?string $plate,
        public int $slotId,
        public int $radius,
        public CarType $carType,
        public array $items,
        public string $startTime,
    ) {}
}
