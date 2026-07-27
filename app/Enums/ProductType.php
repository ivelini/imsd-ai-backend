<?php

namespace App\Enums;

/** Тип товара: шина или диск. */
enum ProductType: string
{
    case Tire = 'tire';
    case Wheel = 'wheel';
}
