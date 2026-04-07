<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function markAsRead(Request $request, DatabaseNotification $notification): RedirectResponse
    {
        abort_unless(
            $notification->notifiable_type === User::class
                && (int) $notification->notifiable_id === (int) $request->user()->id,
            403
        );

        if (is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        return redirect()->to($request->input('redirect_to', route('leave-notices.index')));
    }

    public function destroy(Request $request, DatabaseNotification $notification): RedirectResponse
    {
        abort_unless(
            $notification->notifiable_type === User::class
                && (int) $notification->notifiable_id === (int) $request->user()->id,
            403
        );

        $notification->delete();

        return back();
    }
}
