<?php

namespace App\Enums\Booking;

use App\Enums\Concerns\HasFilamentLabel;
use Filament\Support\Contracts\HasLabel;

/** Канал создания записи: виджет сайта или сотрудник панели. */
enum BookingSource: string implements HasLabel
{
    use HasFilamentLabel;

    case Site = 'site';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Site => 'Сайт',
            self::Admin => 'Админка',
        };
    }
}
