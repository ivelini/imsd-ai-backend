<?php

namespace Tests\Feature\Booking;

use App\Enums\Booking\CodeStatus;
use App\Models\Booking\BookingCode;
use App\Models\System\Setting;
use App\Services\Booking\BookingCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingCodeServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::create(['key' => 'reservation_timeout_min', 'value' => '15']);
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    public function test_issue_creates_hashed_code_with_canonical_phone(): void
    {
        $code = app(BookingCodeService::class)->issue('+7 (900) 123-45-67');

        $this->assertMatchesRegularExpression('/^\d{4}$/', $code);

        $row = BookingCode::query()->firstOrFail();
        $this->assertSame('79001234567', $row->phone);
        $this->assertNull($row->used_at);
        $this->assertSame(hash('sha256', $code.(string) config('app.key')), $row->code_hash);
        $this->assertNotSame($code, $row->code_hash); // plaintext не хранится
    }

    public function test_verify_returns_valid_used_expired_invalid(): void
    {
        $this->travelTo('2026-09-09 10:00:00');
        $service = app(BookingCodeService::class);
        $code = $service->issue('+7 (900) 123-45-67');

        // активный код
        $this->assertSame(CodeStatus::Valid, $service->verify('79001234567', $code)->status);

        // неверный код
        $this->assertSame(CodeStatus::Invalid, $service->verify('79001234567', '0000')->status);

        // использованный код
        BookingCode::query()->firstOrFail()->update(['used_at' => now()]);
        $this->assertSame(CodeStatus::Used, $service->verify('79001234567', $code)->status);

        // просроченный код
        BookingCode::query()->whereNotNull('used_at')->update(['used_at' => null]);
        $this->travelTo('2026-09-09 10:16:00'); // +16 мин > TTL 15
        $this->assertSame(CodeStatus::Expired, $service->verify('79001234567', $code)->status);
    }

    public function test_verify_uses_latest_row_when_code_repeats(): void
    {
        // Стаб-режим (config sms.stub): issue выдаёт один и тот же код — в таблице несколько
        // строк с одинаковым hash. Проверяется свежайшая выдача: использованная старая
        // не должна давать Used при наличии новой активной.
        $hash = hash('sha256', '1234'.config('app.key'));
        BookingCode::create(['phone' => '79001234567', 'code_hash' => $hash, 'used_at' => now()]);
        BookingCode::create(['phone' => '79001234567', 'code_hash' => $hash]);

        $this->assertSame(
            CodeStatus::Valid,
            app(BookingCodeService::class)->verify('79001234567', '1234')->status,
        );

        // новой выдачи нет — повторный ввод использованного кода остаётся Used
        BookingCode::query()->whereNull('used_at')->update(['used_at' => now()]);
        $this->assertSame(
            CodeStatus::Used,
            app(BookingCodeService::class)->verify('79001234567', '1234')->status,
        );
    }
}
