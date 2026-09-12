<?php

namespace App\Enums\Booking;

use App\Enums\Concerns\HasFilamentLabel;
use Carbon\CarbonImmutable;
use Filament\Support\Contracts\HasLabel;

/** Период сетки слотов в листинге панели: день или ISO-неделя (пн–вс). */
enum SlotPeriod: string implements HasLabel
{
    use HasFilamentLabel;

    case Today = 'today';
    case Tomorrow = 'tomorrow';
    case CurrentWeek = 'current_week';
    case NextWeek = 'next_week';

    public function label(): string
    {
        return match ($this) {
            self::Today => 'Сегодня',
            self::Tomorrow => 'Завтра',
            self::CurrentWeek => 'Текущая неделя',
            self::NextWeek => 'Следующая неделя',
        };
    }

    /**
     * Границы периода на момент запроса: начало первого дня и конец последнего — как нужно
     * для `whereBetween` по колонке-дате (каст `date` пишет время, сравнение строк дату не поймает).
     *
     * @return array{from: CarbonImmutable, to: CarbonImmutable}
     */
    public function range(CarbonImmutable $now): array
    {
        $today = $now->startOfDay();

        return match ($this) {
            self::Today => self::dayRange($today),
            self::Tomorrow => self::dayRange($today->addDay()),
            self::CurrentWeek => self::weekRange($today->startOfWeek()),
            self::NextWeek => self::weekRange($today->startOfWeek()->addWeek()),
        };
    }

    /** @return array{from: CarbonImmutable, to: CarbonImmutable} один день целиком */
    private static function dayRange(CarbonImmutable $day): array
    {
        return ['from' => $day->startOfDay(), 'to' => $day->endOfDay()];
    }

    /** @return array{from: CarbonImmutable, to: CarbonImmutable} неделя от понедельника по воскресенье */
    private static function weekRange(CarbonImmutable $monday): array
    {
        return ['from' => $monday->startOfDay(), 'to' => $monday->addDays(6)->endOfDay()];
    }
}
