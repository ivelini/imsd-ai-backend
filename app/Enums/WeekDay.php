<?php

namespace App\Enums;

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
}
