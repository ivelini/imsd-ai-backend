<?php

namespace App\DTOs\Booking;

use App\ValueObjects\Money;

/** Строка состава записи в правке оператора (ФТ-19): цена — за единицу, не за строку. */
final readonly class BookingItemInput
{
    public function __construct(
        public int $serviceId,
        public int $quantity,
        public Money $price,
    ) {}
}
