<?php

use App\Models\Auth\Admin;
use Illuminate\Support\Facades\Broadcast;

/** Приватный канал для уведомлений админа (формат: полное имя модели с точками). */
Broadcast::channel('App.Models.Auth.Admin.{id}', function (Admin $admin, int $id): bool {
    return $admin->id === $id;
});
