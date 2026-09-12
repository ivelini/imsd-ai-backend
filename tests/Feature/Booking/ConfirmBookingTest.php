<?php

namespace Tests\Feature\Booking;

use App\Actions\Booking\ConfirmBooking;
use App\DTOs\Booking\ConfirmBookingInput;
use App\Enums\Booking\BookingSource;
use App\Enums\Booking\BookingStatus;
use App\Enums\Booking\CarType;
use App\Enums\Booking\CodeStatus;
use App\Models\Booking\Booking;
use App\Models\Booking\BookingCode;
use App\Models\Booking\BookingService;
use App\Models\Booking\PriceRule;
use App\Models\Booking\Slot;
use App\Models\System\Setting;
use App\Services\Booking\BookingCodeService;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConfirmBookingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::create(['key' => 'reservation_timeout_min', 'value' => '15']);
    }

    private function serviceWithRule(int $radius = 13, int $price = 15000): BookingService
    {
        $service = BookingService::create([
            'name' => 'Снятие и установка колёс',
            'category' => 'tire',
            'is_active' => true,
            'base_price' => $price,
        ]);
        PriceRule::create([
            'service_id' => $service->id,
            'radius' => $radius,
            'car_type' => CarType::Passenger,
            'price' => $price,
        ]);

        return $service;
    }

    private function input(
        BookingCode $code,
        BookingService $service,
        string $date = '2026-09-10',
        int $hour = 11,
        bool $closeSlot = true,
    ): ConfirmBookingInput {
        return new ConfirmBookingInput(
            code: $code,
            name: 'Иван',
            phone: '79001234567',
            plate: 'А 000 АА 174',
            date: CarbonImmutable::parse($date),
            hour: $hour,
            radius: 13,
            carType: CarType::Passenger,
            quantities: [$service->id => 4],
            closeSlot: $closeSlot,
        );
    }

    private function verifiedCode(string $phone = '79001234567'): BookingCode
    {
        $plain = app(BookingCodeService::class)->issue($phone);

        return app(BookingCodeService::class)->verify($phone, $plain)->code;
    }

    private function openSlot(string $date = '2026-09-10', int $hour = 11): Slot
    {
        return Slot::create(['date' => $date, 'hour' => $hour]);
    }

    public function test_creates_booking_with_snapshot(): void
    {
        $service = $this->serviceWithRule();
        $this->openSlot();
        $code = $this->verifiedCode();

        $booking = app(ConfirmBooking::class)->execute($this->input($code, $service));

        $this->assertSame(BookingStatus::Confirmed, $booking->status);
        $this->assertSame(BookingSource::Site, $booking->source);
        $this->assertSame(13, $booking->radius);
        $this->assertSame(CarType::Passenger, $booking->car_type);
        $this->assertSame('А 000 АА 174', $booking->plate);
        $this->assertSame(60000, $booking->total_price->toKopecks()); // 150 × 4
        $this->assertSame('79001234567', $booking->user->phone); // клиент — единая users

        $item = $booking->items()->firstOrFail();
        $this->assertSame(15000, $item->price->toKopecks()); // цена за единицу
        $this->assertSame(4, $item->quantity);

        // Бронь с сайта занимает час (ФТ-8/ФТ-16): слот закрыт и привязан к записи
        $slot = $booking->slot()->firstOrFail();
        $this->assertTrue($slot->is_closed);
        $this->assertSame($booking->id, $slot->booking_id);

        $this->assertNotNull($code->fresh()->used_at);
    }

    public function test_keeps_slot_open_when_close_slot_false(): void
    {
        $service = $this->serviceWithRule();
        $slot = $this->openSlot();
        $code = $this->verifiedCode();

        // Запись без закрытия (админка, чекбокс не стоял — ФТ-18): слот остаётся открытым
        app(ConfirmBooking::class)->execute($this->input($code, $service, closeSlot: false));

        $this->assertFalse($slot->fresh()->is_closed);
        $this->assertNull($slot->fresh()->booking_id);
        $this->assertSame(1, Booking::count());
    }

    public function test_fails_when_slot_closed(): void
    {
        $service = $this->serviceWithRule();
        $this->openSlot()->update(['is_closed' => true]);
        $code = $this->verifiedCode();

        try {
            app(ConfirmBooking::class)->execute($this->input($code, $service));
            $this->fail('Ожидался DomainException');
        } catch (DomainException $exception) {
            $this->assertSame(409, $exception->getCode());
        }

        $this->assertSame(0, Booking::count());
        $this->assertNull($code->fresh()->used_at);
    }

    public function test_used_code_returns_existing_booking(): void
    {
        $service = $this->serviceWithRule();
        $this->openSlot();
        $plain = app(BookingCodeService::class)->issue('79001234567');
        $verification = app(BookingCodeService::class)->verify('79001234567', $plain);

        $first = app(ConfirmBooking::class)->execute($this->input($verification->code, $service));

        // повторная проверка того же кода — статус Used, запись не дублируется
        $again = app(BookingCodeService::class)->verify('79001234567', $plain);
        $this->assertSame(CodeStatus::Used, $again->status);
        $this->assertTrue($first->is(Booking::forCode($again->code)));
        $this->assertSame(1, Booking::count());
    }
}
