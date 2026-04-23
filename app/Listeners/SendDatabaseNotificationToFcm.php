<?php

namespace App\Listeners;

use App\Models\User;
use App\Notifications\FcmMessage;
use App\Services\FcmNotificationService;
use Illuminate\Notifications\Events\NotificationSent;
use Throwable;

class SendDatabaseNotificationToFcm
{
    public function __construct(
        private readonly FcmNotificationService $fcmNotificationService,
    ) {
    }

    public function handle(NotificationSent $event): void
    {
        if ($event->channel !== 'database' || ! $event->notifiable instanceof User) {
            return;
        }

        if (! method_exists($event->notification, 'toArray')) {
            return;
        }

        $payload = $event->notification->toArray($event->notifiable);
        if (! is_array($payload) || $payload === []) {
            return;
        }

        $notificationId = is_object($event->response) && isset($event->response->id)
            ? (string) $event->response->id
            : null;

        if (
            method_exists($event->notification, 'shouldSendFcmForDatabase')
            && ! $event->notification->shouldSendFcmForDatabase($event->notifiable, $notificationId)
        ) {
            return;
        }

        $message = FcmMessage::fromDatabaseNotificationData(
            $payload,
            $event->notification::class,
            $notificationId,
        );

        try {
            $this->fcmNotificationService->sendToNotifiable(
                $event->notifiable,
                $message,
                $event->notification,
            );
        } catch (Throwable $exception) {
            try {
                report($exception);
            } catch (Throwable) {
                // Swallow reporting failure so chat reply flow never breaks.
            }
        }
    }
}
