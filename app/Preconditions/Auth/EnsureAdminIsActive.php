<?php

namespace App\Preconditions\Auth;

use App\Models\Auth\Admin;
use DomainException;

/** Проверка: аккаунт администратора активен. */
final readonly class EnsureAdminIsActive
{
    public function ensure(Admin $admin): void
    {
        if (! $admin->is_active) {
            throw new DomainException('Аккаунт заблокирован.');
        }
    }
}
