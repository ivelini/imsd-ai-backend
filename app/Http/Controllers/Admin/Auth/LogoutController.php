<?php

namespace App\Http\Controllers\Admin\Auth;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Выход администратора (удаление текущего токена). */
final readonly class LogoutController
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Вы вышли.']);
    }
}
