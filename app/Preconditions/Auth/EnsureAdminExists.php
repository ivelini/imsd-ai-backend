<?php

namespace App\Preconditions\Auth;

use App\Models\Auth\Admin;
use DomainException;

/** Проверка: администратор с таким email существует. */
final readonly class EnsureAdminExists
{
    public function ensure(string $email): Admin
    {
        $admin = Admin::where('email', $email)->first();

        if ($admin === null) {
            throw new DomainException('Неверный email или пароль.', 422);
        }

        return $admin;
    }
}
