<?php

namespace App\Enums\Catalog;

/** Тип товара: шина или диск. */
enum ProductType: string
{
    case Tire = 'tire';
    case Wheel = 'wheel';
}
