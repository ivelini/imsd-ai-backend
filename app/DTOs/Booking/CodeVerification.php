<?php

namespace App\DTOs\Booking;

use App\Enums\Booking\CodeStatus;
use App\Models\Booking\BookingCode;

/**
 * Результат проверки кода (только данные): статус и найденная строка кода
 * (для Valid/Used/Expired). BookingCode при Invalid — null.
 */
final readonly class CodeVerification
{
    public function __construct(
        public CodeStatus $status,
        public ?BookingCode $code = null,
    ) {}
}
