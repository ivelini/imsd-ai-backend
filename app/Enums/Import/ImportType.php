<?php

namespace App\Enums\Import;

/** Тип импорта: шины, диски, точки выдачи, модели. */
enum ImportType: string
{
    case Tire = 'tire';
    case Wheel = 'wheel';
    case Point = 'point';
    case Model = 'model';

    public function label(): string
    {
        return match ($this) {
            self::Tire => 'Шины',
            self::Wheel => 'Диски',
            self::Point => 'Точки выдачи',
            self::Model => 'Модели',
        };
    }
}
