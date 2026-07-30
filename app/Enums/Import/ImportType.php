<?php

namespace App\Enums\Import;

/** Тип импорта: шины, диски, точки выдачи. */
enum ImportType: string
{
    case Tire = 'tire';
    case Wheel = 'wheel';
    case Point = 'point';

    public function label(): string
    {
        return match ($this) {
            self::Tire => 'Шины',
            self::Wheel => 'Диски',
            self::Point => 'Точки выдачи',
        };
    }
}
