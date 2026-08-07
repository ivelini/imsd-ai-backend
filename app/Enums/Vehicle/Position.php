<?php

namespace App\Enums\Vehicle;

/** Положение шины/диска на оси: перед, зад. Null для не-staggered. */
enum Position: string
{
    case Front = 'Front';
    case Rear = 'Rear';
}
