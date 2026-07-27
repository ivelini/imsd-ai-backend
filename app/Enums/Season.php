<?php

namespace App\Enums;

/** Сезонность шины: зимняя, летняя, всесезон. */
enum Season: string
{
    case Winter = 'winter';
    case Summer = 'summer';
    case AllSeason = 'all-season';
}
