<?php

namespace App\DTOs\Booking;

use App\Enums\Booking\CarType;
use App\Models\Auth\Admin;

/** Данные создания записи оператором из панели (ФТ-18 tireslot). */
final readonly class CreateAdminBookingInput
{
    /**
     * @param  array<int, int>  $quantities  service_id => количество 1–4
     * @param  bool  $closeSlot  закрыть слот с привязкой к записи; закрытый слот — не барьер (привязку не перезаписываем)
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
        public bool $closeSlot,
    ) {}
}
