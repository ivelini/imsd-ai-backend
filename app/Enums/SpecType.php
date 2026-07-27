<?php

namespace App\Enums;

/** Тип совместимости: OEM, замена, тюнинг. */
enum SpecType: string
{
    case Oem = 'oem';
    case Replacement = 'replacement';
    case Tuning = 'tuning';
}
