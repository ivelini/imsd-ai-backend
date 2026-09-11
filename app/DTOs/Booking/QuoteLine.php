<?php

namespace App\DTOs\Booking;

use App\Models\Booking\BookingService;

/** Строка расчёта цены: услуга, цена за единицу, количество, итог строки (копейки). */
final readonly class QuoteLine
{
    public function __construct(
        public BookingService $service,
        public int $unitPrice,
        public int $quantity,
        public int $price,
    ) {}
}
