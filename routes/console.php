<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Границы действия акций наступают по времени: цена должна меняться без правок в панели
Schedule::command('promotions:sync')->everyFiveMinutes()->withoutOverlapping();

// Сетка слотов записи на шиномонтаж держится на горизонт записи (ADR 0001 tireslot)
Schedule::command('slots:generate')->everyFifteenMinutes()->withoutOverlapping();
