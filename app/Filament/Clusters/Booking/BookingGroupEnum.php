<?php

namespace App\Filament\Clusters\Booking;

/** Группы поднавигации кластера «Шиномонтаж». */
enum BookingGroupEnum: string
{
    case Services = 'Услуги';
    case Schedule = 'Записи';
    case Settings = 'Настройки';
}
