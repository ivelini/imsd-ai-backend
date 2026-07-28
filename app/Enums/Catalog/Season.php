<?php

namespace App\Enums\Catalog;

/** Сезонность шины: зимняя, летняя, всесезон. */
enum Season: string
{
    case Winter = 'winter';
    case Summer = 'summer';
    case AllSeason = 'all-season';

    public function label(): string
    {
        return match ($this) {
            self::Winter => 'Зимняя',
            self::Summer => 'Летняя',
            self::AllSeason => 'Всесезон',
        };
    }
}
