<?php

use App\Providers\AppServiceProvider;
use App\Providers\CatalogServiceProvider;
use App\Providers\EventServiceProvider;
use App\Providers\ScrambleServiceProvider;

return [
    AppServiceProvider::class,
    EventServiceProvider::class,
    CatalogServiceProvider::class,
    ScrambleServiceProvider::class,

];
