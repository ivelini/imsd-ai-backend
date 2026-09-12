<?php

namespace Tests\Unit\Enums\Booking;

use App\Enums\Booking\SlotPeriod;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

/** Границы периодов сетки слотов: текущий день, завтра и ISO-недели (пн–вс). */
class SlotPeriodTest extends TestCase
{
    public function test_today_range_is_current_day(): void
    {
        $range = SlotPeriod::Today->range(CarbonImmutable::parse('2026-09-12 14:30'));

        self::assertSame('2026-09-12', $range['from']->toDateString());
        self::assertSame('2026-09-12', $range['to']->toDateString());
    }

    public function test_tomorrow_range_is_next_day(): void
    {
        $range = SlotPeriod::Tomorrow->range(CarbonImmutable::parse('2026-09-12 14:30'));

        self::assertSame('2026-09-13', $range['from']->toDateString());
        self::assertSame('2026-09-13', $range['to']->toDateString());
    }

    /** Границы считаются от понедельника: «от сегодня до воскресенья» этот тест завалит. */
    public function test_current_week_range_is_monday_to_sunday(): void
    {
        $range = SlotPeriod::CurrentWeek->range(CarbonImmutable::parse('2026-09-12 14:30'));

        self::assertSame('2026-09-07', $range['from']->toDateString());
        self::assertSame('2026-09-13', $range['to']->toDateString());
    }

    /** Воскресенье — последний день недели: следующий понедельник в диапазон не входит. */
    public function test_current_week_excludes_next_monday(): void
    {
        $range = SlotPeriod::CurrentWeek->range(CarbonImmutable::parse('2026-09-13 09:00'));

        self::assertSame('2026-09-07', $range['from']->toDateString());
        self::assertSame('2026-09-13', $range['to']->toDateString());
    }

    public function test_next_week_range_is_following_monday_to_sunday(): void
    {
        $range = SlotPeriod::NextWeek->range(CarbonImmutable::parse('2026-09-12 14:30'));

        self::assertSame('2026-09-14', $range['from']->toDateString());
        self::assertSame('2026-09-20', $range['to']->toDateString());
    }
}
