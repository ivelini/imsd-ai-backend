<?php

namespace App\Enums\Booking;

use App\Enums\Concerns\HasFilamentLabel;
use Carbon\CarbonImmutable;
use Filament\Support\Contracts\HasLabel;

/** Пресет периода сетки слотов в листинге панели: день, завтра, календарные неделя и месяц. */
enum SlotPeriod: string implements HasLabel
{
    use HasFilamentLabel;

    case Today = 'today';
    case Tomorrow = 'tomorrow';
    case CurrentWeek = 'current_week';
    case CurrentMonth = 'current_month';

    public function label(): string
    {
        return match ($this) {
            self::Today => 'Сегодня',
            self::Tomorrow => 'Завтра',
            self::CurrentWeek => 'Текущая неделя',
            self::CurrentMonth => 'Текущий месяц',
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
        // Неделя — от понедельника по воскресенье: «от сегодня до воскресенья» дало бы другую сетку.
        $monday = $today->startOfWeek();

        return match ($this) {
            self::Today => self::spanRange($today, $today),
            self::Tomorrow => self::spanRange($today->addDay(), $today->addDay()),
            self::CurrentWeek => self::spanRange($monday, $monday->addDays(6)),
            self::CurrentMonth => self::spanRange($today->startOfMonth(), $today->endOfMonth()),
        };
    }

    /**
     * @return array{from: CarbonImmutable, to: CarbonImmutable} отрезок от первого дня до последнего включительно
     */
    private static function spanRange(CarbonImmutable $first, CarbonImmutable $last): array
    {
        return ['from' => $first->startOfDay(), 'to' => $last->endOfDay()];
    }
}
