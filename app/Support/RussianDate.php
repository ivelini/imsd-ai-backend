<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Русские названия дат для страниц записи и бумаг панели (Carbon-локализация в проекте не подключена):
 * «Сентябрь 2026», «11 сентября, пятница», «11 сентября (пт)», «11 сентября 2026».
 */
final class RussianDate
{
    private const MONTHS_NOMINATIVE = [
        1 => 'Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь',
        'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь',
    ];

    private const MONTHS_GENITIVE = [
        1 => 'января', 'февраля', 'марта', 'апреля', 'мая', 'июня',
        'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря',
    ];

    private const WEEKDAYS = [
        1 => 'понедельник', 'вторник', 'среда', 'четверг', 'пятница', 'суббота', 'воскресенье',
    ];

    private const WEEKDAYS_SHORT = [
        1 => 'пн', 'вт', 'ср', 'чт', 'пт', 'сб', 'вс',
    ];

    private function __construct() {}

    public static function monthTitle(CarbonImmutable $date): string
    {
        return self::MONTHS_NOMINATIVE[$date->month].' '.$date->year;
    }

    /** «11 сентября, пятница» */
    public static function dayWithWeekday(CarbonImmutable $date): string
    {
        return sprintf('%d %s, %s', $date->day, self::MONTHS_GENITIVE[$date->month], self::WEEKDAYS[$date->isoWeekday()]);
    }

    /** «11 сентября (пт)» */
    public static function dayShort(CarbonImmutable $date): string
    {
        return sprintf('%d %s (%s)', $date->day, self::MONTHS_GENITIVE[$date->month], self::WEEKDAYS_SHORT[$date->isoWeekday()]);
    }

    /** «11 сентября 2026» — длинная дата с годом (договор хранения) */
    public static function dayWithYear(CarbonInterface $date): string
    {
        return sprintf('%d %s %d', $date->day, self::MONTHS_GENITIVE[$date->month], $date->year);
    }
}
