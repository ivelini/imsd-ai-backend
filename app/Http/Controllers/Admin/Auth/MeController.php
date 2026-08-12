<?php

namespace App\Http\Controllers\Admin\Auth;

use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Профиль текущего администратора. */
#[Group('Профиль', weight: 1)]
final readonly class MeController
{
    /**
     * Профиль администратора.
     *
     * Возвращает данные текущего авторизованного администратора.
     */
    public function __invoke(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $request->user()->toArray(),
        ]);
    }
}
