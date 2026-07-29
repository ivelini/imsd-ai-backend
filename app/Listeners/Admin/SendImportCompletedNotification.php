<?php

namespace App\Listeners\Admin;

use App\Events\Admin\ImportCompleted;
use App\Models\Auth\Admin;
use App\Notifications\Admin\ImportCompletedNotification;
use Illuminate\Support\Facades\Notification;

/** Отправить уведомление всем активным админам о завершении импорта. */
final readonly class SendImportCompletedNotification
{
    public function handle(ImportCompleted $event): void
    {
        $admins = Admin::where('is_active', true)->get();

        Notification::send($admins, new ImportCompletedNotification($event->import));
    }
}
