<?php

namespace App\DTOs\Auth;

use App\Models\Auth\Admin;

/** Результат Action LoginAdmin. */
final readonly class LoginAdminResult
{
    public function __construct(
        public Admin $admin,
        public string $token,
    ) {}
}
