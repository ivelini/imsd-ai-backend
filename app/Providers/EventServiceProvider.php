<?php

namespace App\Providers;

use App\Events\Admin\ImportCompleted;
use App\Listeners\Admin\SendImportCompletedNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /** @var array<string, array<int, string>> */
    protected $listen = [
        ImportCompleted::class => [
            SendImportCompletedNotification::class,
        ],
    ];

    /** Отключаем авто-дискавери — all listeners явно указаны в $listen. */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
