<?php

namespace App\DTOs\Booking;

use App\Models\Booking\BookingService;
use App\ValueObjects\Money;

/** Строка расчёта цены: услуга, цена за единицу, количество, итог строки. */
final readonly class QuoteLine
{
    public function __construct(
        public BookingService $service,
        public Money $unitPrice,
        public int $quantity,
        public Money $price,
    ) {}
}
