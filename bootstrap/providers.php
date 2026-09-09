<?php

use App\Providers\AppServiceProvider;
use App\Providers\CatalogServiceProvider;
use App\Providers\EventServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\ScrambleServiceProvider;

return [
    AppServiceProvider::class,
    CatalogServiceProvider::class,
    EventServiceProvider::class,
    AdminPanelProvider::class,
    ScrambleServiceProvider::class,
];
