<?php

namespace App\Enums\Catalog;

/** Материал диска: литой, штампованный, кованый. */
enum WheelType: string
{
    case Alloy = 'alloy';
    case Steel = 'steel';
    case Forged = 'forged';

    public function label(): string
    {
        return match ($this) {
            self::Alloy => 'Литые',
            self::Steel => 'Стальные',
            self::Forged => 'Кованые',
        };
    }
}
