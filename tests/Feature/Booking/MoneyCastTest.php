<?php

namespace Tests\Feature\Booking;

use App\Models\Booking\BookingService;
use App\ValueObjects\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MoneyCastTest extends TestCase
{
    use RefreshDatabase;

    public function test_money_field_returns_vo_and_accepts_money_or_int(): void
    {
        $service = BookingService::create([
            'name' => 'Замена вентиля',
            'category' => 'tire',
            'is_active' => true,
            'base_price' => Money::fromRubles(150),
        ]);

        $this->assertInstanceOf(Money::class, $service->base_price);
        $this->assertSame(15000, $service->base_price->toKopecks());

        // int-ввод (фабрики, сидеры) тоже проходит через каст
        $service->update(['base_price' => 30000]);
        $this->assertSame(30000, $service->fresh()->base_price->toKopecks());
    }
}
