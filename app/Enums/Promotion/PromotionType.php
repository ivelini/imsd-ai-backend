<?php

namespace App\Enums\Promotion;

/** Тип акции: процент, фиксированная сумма, подарок, спеццена. */
enum PromotionType: string
{
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
