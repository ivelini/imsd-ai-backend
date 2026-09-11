<?php

namespace App\Enums\Promotion;

use App\Enums\Concerns\HasFilamentLabel;
use Filament\Support\Contracts\HasLabel;

/** Тип акции: процент, фиксированная сумма, подарок, спеццена. */
enum PromotionType: string implements HasLabel
{
    use HasFilamentLabel;

    case Percent = 'percent';
    case Fixed = 'fixed';
    case Gift = 'gift';
    case Special = 'special';

    public function label(): string
    {
        return match ($this) {
            self::Percent => 'Процент',
            self::Fixed => 'Фиксированная сумма',
            self::Gift => 'Подарок',
            self::Special => 'Спеццена',
        };
    }
}
