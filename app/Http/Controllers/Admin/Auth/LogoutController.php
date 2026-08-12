<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Models\Auth\Admin;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Выход администратора (удаление текущего токена). */
#[Group('Профиль', weight: 1)]
final readonly class LogoutController
{
    /**
     * Выход из админ-панели.
     *
     * Удаляет текущий Sanctum-токен.
     */
    public function __invoke(Request $request): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        $admin->currentAccessToken()->delete();

        return response()->json(['message' => 'Вы вышли.']);
    }
}
