<?php

namespace App\Enums\Catalog;

/** Сезонность шины: зимняя, летняя, всесезон. */
enum Season: string
{
    case Winter = 'winter';
    case Summer = 'summer';
    case AllSeason = 'all-season';
}
