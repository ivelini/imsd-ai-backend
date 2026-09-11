<?php

namespace App\Preconditions\Booking;

use App\DTOs\Booking\CodeVerification;
use App\Enums\Booking\CodeStatus;
use App\Services\Booking\BookingCodeService;
use DomainException;

/**
 * Проверка кода подтверждения перед созданием записи: Valid → возвращает
 * результат проверки (строка кода нужна Action), Expired/Invalid → DomainException.
 * Used пропускается — контроллер вернёт уже созданную запись (повторный submit).
 */
final readonly class EnsureCodeVerifiable
{
    public function __construct(private BookingCodeService $codes) {}

    public function ensure(string $phone, string $code): CodeVerification
    {
        $verification = $this->codes->verify($phone, $code);

        if ($verification->status === CodeStatus::Invalid) {
            throw new DomainException('Код не найден', 422);
        }

        if ($verification->status === CodeStatus::Expired) {
            throw new DomainException('Код истёк, запросите новый', 409);
        }

        return $verification;
    }
}
