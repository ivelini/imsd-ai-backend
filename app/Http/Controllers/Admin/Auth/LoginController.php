<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Actions\Auth\LoginAdmin;
use App\Http\Requests\Admin\Auth\LoginRequest;
use App\Http\Resources\Admin\Auth\LoginResource;
use App\Preconditions\Auth\EnsureAdminExists;
use App\Preconditions\Auth\EnsureAdminIsActive;
use App\Preconditions\Auth\EnsurePasswordIsValid;

/** Аутентификация администратора. */
final class LoginController
{
    public function __construct(
        private EnsureAdminExists $ensureAdminExists,
        private EnsurePasswordIsValid $ensurePasswordIsValid,
        private EnsureAdminIsActive $ensureAdminIsActive,
        private LoginAdmin $loginAdmin,
    ) {}

    /**
     * Вход в админ-панель.
     *
     * Возвращает Sanctum-токен для авторизации в остальных эндпоинтах.
     */
    public function __invoke(LoginRequest $request): LoginResource
    {
        $data = $request->validated();

        $admin = $this->ensureAdminExists->ensure($data['email']);
        $this->ensurePasswordIsValid->ensure($data['password'], $admin->password);
        $this->ensureAdminIsActive->ensure($admin);

        $result = $this->loginAdmin->execute($admin);

        return new LoginResource($result->admin, $result->token);
    }
}
