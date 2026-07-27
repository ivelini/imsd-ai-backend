<?php

namespace App\Enums\Promotion;

/** Тип акции: процент, фиксированная сумма, подарок, спеццена. */
enum PromotionType: string
{
    case Percent = 'percent';
    case Fixed = 'fixed';
    case Gift = 'gift';
    case Special = 'special';
}
