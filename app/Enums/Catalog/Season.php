<?php

namespace App\Enums\Catalog;

use App\Enums\Concerns\HasFilamentLabel;
use Filament\Support\Contracts\HasLabel;

/** Сезонность шины: зимняя, летняя, всесезон. */
enum Season: string implements HasLabel
{
    use HasFilamentLabel;

    case Winter = 'winter';
    case Summer = 'summer';
    case AllSeason = 'all-season';

    public function label(): string
    {
        return match ($this) {
            self::Winter => 'Зимняя',
            self::Summer => 'Летняя',
            self::AllSeason => 'Всесезонная',
        };
    }
}
