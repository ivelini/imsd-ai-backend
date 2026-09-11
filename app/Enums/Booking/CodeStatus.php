<?php

namespace App\Enums\Booking;

/**
 * Результат проверки кода подтверждения (BookingCodeService::verify):
 * Valid — активный код можно подтверждать; Used — код уже создал запись
 * (повторный submit возвращает существующую запись); Expired — истёк
 * срок действия (TTL); Invalid — кода для телефона нет.
 */
enum CodeStatus: string
{
    case Valid = 'valid';
    case Used = 'used';
    case Expired = 'expired';
    case Invalid = 'invalid';
}
