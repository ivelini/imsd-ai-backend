<?php

namespace App\Enums\Booking;

use App\Enums\Concerns\HasFilamentLabel;
use Filament\Support\Contracts\HasLabel;

/** Категория услуги шиномонтажа. */
enum ServiceCategory: string implements HasLabel
{
    use HasFilamentLabel;

    case Tire = 'tire';
    case Storage = 'storage';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Tire => 'Шиномонтаж',
            self::Storage => 'Хранение',
            self::Other => 'Прочее',
        };
    }
}
