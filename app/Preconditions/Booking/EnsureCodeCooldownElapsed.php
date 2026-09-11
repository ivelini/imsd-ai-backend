<?php

namespace App\Preconditions\Booking;

use App\Services\Booking\BookingCodeService;
use DomainException;

/**
 * Кулдаун повторной отправки SMS-кода (config sms.resend_cooldown_seconds).
 * Серверная защита: в tireslot кулдаун был только в UI, API обязан проверять сам.
 */
final readonly class EnsureCodeCooldownElapsed
{
    public function __construct(private BookingCodeService $codes) {}

    public function ensure(string $phone): void
    {
        $lastIssuedAt = $this->codes->lastIssuedAt($phone);
        $elapsed = $lastIssuedAt?->diffInSeconds(now()) ?? config('sms.resend_cooldown_seconds');

        if ($elapsed < config('sms.resend_cooldown_seconds')) {
            throw new DomainException('Код уже отправлен, повторите позже', 429);
        }
    }
}
