<?php

namespace App\Enums\Common;

/** Тип совместимости: OEM, замена, тюнинг. */
enum SpecType: string
{
    case Oem = 'oem';
    case Replacement = 'replacement';
    case Tuning = 'tuning';
}
