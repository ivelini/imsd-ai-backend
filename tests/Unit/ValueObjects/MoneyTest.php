<?php

namespace Tests\Unit\ValueObjects;

use App\ValueObjects\Money;
use InvalidArgumentException;
use Tests\TestCase;

class MoneyTest extends TestCase
{
    public function test_formatted_renders_rubles(): void
    {
        $this->assertSame('1 700 ₽', Money::fromKopecks(170000)->formatted());
        $this->assertSame('1 700,50 ₽', Money::fromKopecks(170050)->formatted());
        $this->assertSame('0 ₽', Money::fromKopecks(0)->formatted());
    }

    public function test_from_rubles_rounds_and_accepts_strings(): void
    {
        $this->assertSame(170050, Money::fromRubles(1700.5)->toKopecks());
        $this->assertSame(170000, Money::fromRubles('1700')->toKopecks());
        $this->assertSame(15000, Money::fromRubles('150')->toKopecks());
        $this->assertSame(1.5, Money::fromRubles(1.5)->toRubles()); // toRubles round-trip
    }

    public function test_arithmetic_works_only_via_money(): void
    {
        $unit = Money::fromKopecks(15000);

        $this->assertSame(60000, $unit->multiply(4)->toKopecks());
        $this->assertSame(88000, $unit->multiply(4)->add(Money::fromKopecks(28000))->toKopecks());
    }

    public function test_multiply_rejects_zero_and_negative_factor(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::fromKopecks(1000)->multiply(0);
    }

    public function test_negative_money_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::fromKopecks(-1);
    }

    public function test_json_serializes_to_kopecks(): void
    {
        $this->assertSame(170000, json_decode(json_encode(Money::fromKopecks(170000)), true));
    }

    /** Полезная нагрузка Livewire — массив: WireableSynth итерирует её и требует array-shaped payload. */
    public function test_livewire_payload_is_array_shaped(): void
    {
        $this->assertIsArray(Money::fromKopecks(170000)->toLivewire());
    }

    public function test_livewire_roundtrip_keeps_kopecks(): void
    {
        $money = Money::fromKopecks(170000);

        $this->assertSame(170000, Money::fromLivewire($money->toLivewire())->toKopecks());
        // Числовая форма осталась в снапшотах уже открытых страниц — читается и она
        $this->assertSame(170000, Money::fromLivewire(170000)->toKopecks());
    }
}
