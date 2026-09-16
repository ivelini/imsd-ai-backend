<?php

namespace Tests\Unit\Enums\Booking;

use App\Enums\Booking\SlotPeriod;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

/** Границы пресетов периода сетки слотов: день, завтра, ISO-неделя (пн–вс) и календарный месяц. */
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

    /** Месяц — от первого числа до последнего: конец последнего дня включён (иначе слоты 30-го числа выпадут). */
    public function test_current_month_range_is_first_to_last_day(): void
    {
        $range = SlotPeriod::CurrentMonth->range(CarbonImmutable::parse('2026-09-12 14:30'));

        self::assertSame('2026-09-01 00:00:00', $range['from']->toDateTimeString());
        self::assertSame('2026-09-30 23:59:59', $range['to']->toDateTimeString());
    }

    /** Границы месяца считаются по календарю, а не «30 дней»: февраль короче. */
    public function test_current_month_range_respects_month_length(): void
    {
        $range = SlotPeriod::CurrentMonth->range(CarbonImmutable::parse('2026-02-10 09:00'));

        self::assertSame('2026-02-01', $range['from']->toDateString());
        self::assertSame('2026-02-28 23:59:59', $range['to']->toDateTimeString());
    }
}
