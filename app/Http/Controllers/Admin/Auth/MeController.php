<?php

namespace App\Http\Controllers\Admin\Auth;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Профиль текущего администратора. */
final readonly class MeController
{
    public function __invoke(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $request->user()->toArray(),
        ]);
    }
}
