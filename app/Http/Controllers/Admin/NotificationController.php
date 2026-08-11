<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\NotificationIndexRequest;
use App\Http\Resources\Admin\NotificationResource;
use App\Models\Auth\Admin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Уведомления для админ-панели (список, прочтение). */
final readonly class NotificationController
{
    /** Список уведомлений текущего админа. */
    public function index(NotificationIndexRequest $request): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        $query = $admin->notifications();

        if ($request->boolean('unread_only')) {
            $query->whereNull('read_at');
        }

        $notifications = $query
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return response()->json([
            'data' => NotificationResource::collection($notifications),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
                'unread_count' => $admin->unreadNotifications()->count(),
            ],
        ]);
    }

    /** Отметить уведомление прочитанным. */
    public function read(Request $request, string $id): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        $notification = $admin->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json(['message' => 'OK']);
    }

    /** Отметить все уведомления прочитанными. */
    public function readAll(Request $request): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        $admin->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['message' => 'OK']);
    }
}
