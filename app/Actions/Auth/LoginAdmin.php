<?php

namespace App\Actions\Auth;

use App\DTOs\Auth\LoginAdminResult;
use App\Models\Auth\Admin;

/** Выдать Sanctum-токен администратору. */
final readonly class LoginAdmin
{
    public function execute(Admin $admin): LoginAdminResult
    {
        $token = $admin->createToken('admin-token')->plainTextToken;

        return new LoginAdminResult($admin, $token);
    }
}
