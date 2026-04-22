<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\FcmNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function __construct(
        private readonly FcmNotificationService $fcmNotificationService,
    ) {
    }

    public function markAsRead(Request $request, DatabaseNotification $notification): RedirectResponse
    {
        abort_unless(
            $notification->notifiable_type === User::class
                && (int) $notification->notifiable_id === (int) $request->user()->id,
            403
        );

        $notificationId = (string) $notification->id;

        if (is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        $notification->delete();
        $this->fcmNotificationService->sendSyncActionToNotifiable($request->user(), 'read', $notificationId);

        return redirect()->to($request->input('redirect_to', route('leave-notices.index')));
    }

    public function destroy(Request $request, DatabaseNotification $notification): RedirectResponse
    {
        abort_unless(
            $notification->notifiable_type === User::class
                && (int) $notification->notifiable_id === (int) $request->user()->id,
            403
        );

        $notificationId = (string) $notification->id;
        $notification->delete();
        $this->fcmNotificationService->sendSyncActionToNotifiable($request->user(), 'remove', $notificationId);

        return back();
    }

    public function destroyAll(Request $request): RedirectResponse
    {
        $notifications = $request->user()->unreadNotifications()->get();

        foreach ($notifications as $notification) {
            $notificationId = (string) $notification->id;
            $notification->delete();
            $this->fcmNotificationService->sendSyncActionToNotifiable($request->user(), 'remove', $notificationId);
        }

        return back();
    }
}
