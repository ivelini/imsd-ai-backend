<?php

namespace App\Http\Controllers\Admin\Auth;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Профиль текущего администратора. */
final readonly class MeController
{
    /**
     * Профиль администратора.
     *
     * Возвращает данные текущего авторизованного администратора.
     *
     * @group Аутентификация
     *
     * @authenticated
     */
    public function __invoke(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $request->user()->toArray(),
        ]);
    }
}
