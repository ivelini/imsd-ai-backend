<?php

namespace App\Enums\Catalog;

/** Тип совместимости: OEM, замена, тюнинг. */
enum SpecType: string
{
    case Oem = 'oem';
    case Replacement = 'replacement';
    case Tuning = 'tuning';
}
