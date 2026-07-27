<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Requests\Admin\Auth\LoginRequest;
use App\Http\Resources\Admin\Auth\LoginResource;
use App\Models\Auth\Admin;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/** Аутентификация администратора. */
final class LoginController
{
    /**
     * Вход в админ-панель.
     *
     * Возвращает Sanctum-токен для авторизации в остальных эндпоинтах.
     *
     * @group Аутентификация
     *
     * @unauthenticated
     *
     * @responseField token string Sanctum-токен для последующих запросов.
     * @responseField admin.id int ID администратора.
     * @responseField admin.email string Email администратора.
     * @responseField admin.role string Код роли (super-admin, content-manager, …).
     */
    public function __invoke(LoginRequest $request): LoginResource|JsonResponse
    {
        $admin = Admin::where('email', $request->email)->first();

        if (! $admin || ! Hash::check($request->password, $admin->password)) {
            throw ValidationException::withMessages([
                'email' => ['Неверный email или пароль.'],
            ]);
        }

        if (! $admin->is_active) {
            return response()->json([
                'message' => 'Аккаунт заблокирован.',
            ], 403);
        }

        $token = $admin->createToken('admin-token')->plainTextToken;

        return new LoginResource($admin, $token);
    }
}
