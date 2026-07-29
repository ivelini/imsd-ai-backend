<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\NotificationIndexRequest;
use App\Http\Resources\Admin\NotificationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/** Уведомления для админ-панели (список, прочтение). */
final readonly class NotificationController
{
    /**
     * Список уведомлений текущего админа.
     *
     * @group Уведомления
     */
    public function index(NotificationIndexRequest $request): JsonResponse
    {
        $query = Auth::user()->notifications();

        if ($request->boolean('unread_only')) {
            $query->whereNull('read_at');
        }

        $notifications = $query
            ->orderBy('created_at', 'desc')
            ->paginate($request->integer('per_page', 20));

        return response()->json([
            'data' => NotificationResource::collection($notifications),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
                'unread_count' => Auth::user()->unreadNotifications()->count(),
            ],
        ]);
    }

    /**
     * Отметить уведомление прочитанным.
     *
     * @group Уведомления
     */
    public function read(string $id): JsonResponse
    {
        $notification = Auth::user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json(['message' => 'OK']);
    }

    /**
     * Отметить все уведомления прочитанными.
     *
     * @group Уведомления
     */
    public function readAll(): JsonResponse
    {
        Auth::user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['message' => 'OK']);
    }
}
