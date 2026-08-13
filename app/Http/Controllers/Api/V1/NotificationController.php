<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends BaseController
{
    /**
     * Lista de notificaciones del usuario
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $query = Notification::where('user_id', $userId)->orderBy('created_at', 'desc');

        if ($request->boolean('unread')) {
            $query->unread();
        }

        $notifications = $query->paginate($request->input('per_page', 20));

        return $this->paginated(
            $notifications,
            $notifications->items(),
            'Notificaciones obtenidas exitosamente.'
        );
    }

    /**
     * Conteo de notificaciones no leídas
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $count = Notification::where('user_id', $userId)->unread()->count();

        return $this->success([
            'unread_count' => $count,
        ], 'Conteo de notificaciones no leídas.');
    }

    /**
     * Marcar notificación individual como leída
     */
    public function markRead(Request $request, Notification $notification): JsonResponse
    {
        if ($notification->user_id !== $request->user()->id) {
            return $this->forbidden();
        }

        $notification->markAsRead();

        return $this->success($notification, 'Notificación marcada como leída.');
    }

    /**
     * Marcar todas las notificaciones como leídas
     */
    public function markAllRead(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        Notification::where('user_id', $userId)
            ->unread()
            ->update(['read_at' => now()]);

        return $this->success(null, 'Todas las notificaciones han sido marcadas como leídas.');
    }
}
