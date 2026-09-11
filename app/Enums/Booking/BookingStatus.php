<?php

namespace App\Enums\Booking;

use App\Enums\Concerns\HasFilamentLabel;
use Filament\Support\Contracts\HasLabel;

/** Статусная машина записи: подтверждена → приехал/неявка → завершена/отменена. */
enum BookingStatus: string implements HasLabel
{
    use HasFilamentLabel;

    case Confirmed = 'confirmed';
    case Arrived = 'arrived';
    case Done = 'done';
    case Cancelled = 'cancelled';
    case NoShow = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::Confirmed => 'Подтверждена',
            self::Arrived => 'Клиент приехал',
            self::Done => 'Завершена',
            self::Cancelled => 'Отменена',
            self::NoShow => 'Неявка',
        };
    }
}
