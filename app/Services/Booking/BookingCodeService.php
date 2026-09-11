<?php

namespace App\Services\Booking;

use App\DTOs\Booking\CodeVerification;
use App\Enums\Booking\CodeStatus;
use App\Enums\System\SettingKeyEnum;
use App\Models\Booking\BookingCode;
use App\Models\System\Setting;
use App\Support\Phone;
use Carbon\CarbonImmutable;
use InvalidArgumentException;

/**
 * Коды подтверждения (ФТ-7/ФТ-9/ФТ-23 tireslot): строка на телефон с одноразовым кодом.
 * Код хранится только хэшем (sha256 кода + ключ приложения); plaintext нужен
 * лишь для SMS и не сохраняется. Заявка не хранится.
 */
class BookingCodeService
{
    /**
     * Создаёт строку кода и возвращает plain-код для отправки по SMS.
     * Повторный запрос не аннулирует старые активные коды телефона (TTL — крон).
     */
    public function issue(string $phone): int
    {
        $canonical = Phone::normalize($phone);
        if ($canonical === null) {
            throw new InvalidArgumentException("Невалидный телефон: {$phone}");
        }

        $code = $this->buildCode();

        BookingCode::create([
            'phone' => $canonical,
            'code_hash' => $this->hash((string) $code),
        ]);

        return $code;
    }

    private function buildCode(): int
    {
        if (config('sms.stub.is_active')) {
            return (int) config('sms.stub.code');
        }

        return random_int(1000, 9999);
    }

    /**
     * Проверяет код телефона: Valid (активен, в TTL) / Used (уже создал запись —
     * повторный submit вернёт существующую) / Expired (TTL истёк) / Invalid.
     */
    public function verify(string $phone, string $code): CodeVerification
    {
        $canonical = Phone::normalize($phone);
        $row = $canonical === null
            ? null
            : BookingCode::query()
                ->where('phone', $canonical)
                ->where('code_hash', $this->hash($code))
                // Свежайшая выдача с этим кодом: при совпадающих hash (стаб-код для ручных тестов)
                // first() брал старую использованную строку и повторная бронь «тихо» уходила в Used
                ->latest('id')
                ->first();

        if ($row === null) {
            return new CodeVerification(CodeStatus::Invalid);
        }

        if ($row->used_at !== null) {
            return new CodeVerification(CodeStatus::Used, $row);
        }

        if ($row->created_at->lt(now()->subMinutes(Setting::get(SettingKeyEnum::ReservationTimeoutMin)))) {
            return new CodeVerification(CodeStatus::Expired, $row);
        }

        return new CodeVerification(CodeStatus::Valid, $row);
    }

    /** Последний (самый свежий) код телефона — для кулдауна повторной отправки. */
    public function lastIssuedAt(string $phone): ?CarbonImmutable
    {
        $canonical = Phone::normalize($phone);
        if ($canonical === null) {
            return null;
        }

        $createdAt = BookingCode::query()->where('phone', $canonical)->latest('id')->value('created_at');

        return $createdAt === null ? null : CarbonImmutable::instance($createdAt);
    }

    private function hash(string $code): string
    {
        return hash('sha256', $code.config('app.key'));
    }
}
