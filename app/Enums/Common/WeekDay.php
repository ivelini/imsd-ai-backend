<?php

namespace App\Enums\Common;

/** День недели для графика отгрузки: 0=пн … 6=вс. */
enum WeekDay: int
{
    case Monday = 0;
    case Tuesday = 1;
    case Wednesday = 2;
    case Thursday = 3;
    case Friday = 4;
    case Saturday = 5;
    case Sunday = 6;

    public function label(): string
    {
        return match ($this) {
            self::Monday => 'Понедельник',
            self::Tuesday => 'Вторник',
            self::Wednesday => 'Среда',
            self::Thursday => 'Четверг',
            self::Friday => 'Пятница',
            self::Saturday => 'Суббота',
            self::Sunday => 'Воскресенье',
        };
    }
}
