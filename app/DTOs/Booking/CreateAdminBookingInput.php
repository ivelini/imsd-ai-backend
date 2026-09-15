<?php

namespace App\DTOs\Booking;

use App\Enums\Booking\CarType;
use App\Models\Auth\Admin;

/** Данные создания записи оператором из панели (ФТ-18). */
final readonly class CreateAdminBookingInput
{
    /**
     * @param  array<int, int>  $quantities  service_id => количество 1–4
     * @param  string  $startTime  время начала «HH:MM:00» внутри часа слота: ровно на начало часа — час занимается
     */
    public function __construct(
        public Admin $operator,
        public string $phone,
        public string $name,
        public ?string $plate,
        public int $slotId,
        public int $radius,
        public CarType $carType,
        public array $quantities,
        public string $startTime,
    ) {}
}
