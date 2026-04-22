<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\FcmNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class N8nNotificationController extends Controller
{
    public function __construct(
        private readonly FcmNotificationService $fcmNotificationService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'unread_only' => ['nullable', 'boolean'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $limit = (int) ($validated['limit'] ?? 50);
        $unreadOnly = (bool) ($validated['unread_only'] ?? true);

        $query = DatabaseNotification::query()
            ->where('notifiable_type', User::class)
            ->when(
                isset($validated['user_id']),
                fn ($q) => $q->where('notifiable_id', (int) $validated['user_id'])
            )
            ->when(
                ! isset($validated['user_id']),
                fn ($q) => $q->whereHasMorph(
                    'notifiable',
                    [User::class],
                    fn ($userQuery) => $userQuery->role(['master_admin', 'admin'])
                )
            )
            ->when($unreadOnly, fn ($q) => $q->whereNull('read_at'))
            ->with('notifiable')
            ->latest()
            ->limit($limit);

        $notifications = $query->get()->map(function (DatabaseNotification $notification): array {
            /** @var User|null $user */
            $user = $notification->notifiable instanceof User ? $notification->notifiable : null;

            return [
                'id' => $notification->id,
                'type' => $notification->type,
                'notifiable' => [
                    'id' => $user?->id,
                    'name' => $user?->display_name,
                    'full_name' => $user?->name,
                    'email' => $user?->email,
                ],
                'data' => $notification->data,
                'created_at' => optional($notification->created_at)?->toIso8601String(),
                'read_at' => optional($notification->read_at)?->toIso8601String(),
            ];
        });

        return response()->json([
            'data' => $notifications,
            'meta' => [
                'count' => $notifications->count(),
                'unread_only' => $unreadOnly,
                'limit' => $limit,
            ],
        ]);
    }

    public function markAsRead(DatabaseNotification $notification): JsonResponse
    {
        if ($notification->notifiable_type !== User::class) {
            return response()->json([
                'message' => 'Notification target is not supported.',
            ], 422);
        }

        $notifiable = $notification->notifiable;
        $notificationId = (string) $notification->id;

        if (is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        if ($notifiable instanceof User) {
            $this->fcmNotificationService->sendSyncActionToNotifiable($notifiable, 'read', $notificationId);
        }

        $notification->delete();

        return response()->json([
            'message' => 'Notification marked as read and removed.',
            'id' => $notificationId,
            'read_at' => now()->toIso8601String(),
        ]);
    }
}
