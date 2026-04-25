<?php

namespace App\Services;

use App\Models\FcmToken;
use App\Notifications\FcmMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Throwable;

class FcmNotificationService
{
    public function __construct(
        private readonly FirebaseAccessTokenService $accessTokenService,
    ) {}

    public function sendToNotifiable(object $notifiable, FcmMessage $message, Notification $notification): void
    {
        if (! $this->supportsFcmRouting($notifiable)) {
            return;
        }

        $tokens = array_values(array_unique(array_filter(
            (array) $notifiable->routeNotificationForFcm(),
            static fn ($token) => is_string($token) && $token !== ''
        )));

        if ($tokens === [] || ! $this->accessTokenService->isConfigured()) {
            return;
        }

        $accessToken = $this->accessTokenService->getAccessToken();
        if (! is_string($accessToken) || $accessToken === '') {
            return;
        }

        $platforms = FcmToken::query()
            ->whereIn('token', $tokens)
            ->pluck('platform', 'token');

        foreach ($tokens as $token) {
            $this->sendToToken(
                $token,
                $message,
                $accessToken,
                $platforms[$token] ?? null,
            );
        }
    }

    public function sendSyncActionToNotifiable(object $notifiable, string $action, string $notificationId): void
    {
        if (! $this->supportsFcmRouting($notifiable)) {
            return;
        }

        $tokens = array_values(array_unique(array_filter(
            (array) $notifiable->routeNotificationForFcm(),
            static fn ($token) => is_string($token) && $token !== ''
        )));

        if ($tokens === [] || ! $this->accessTokenService->isConfigured()) {
            return;
        }

        $accessToken = $this->accessTokenService->getAccessToken();
        if (! is_string($accessToken) || $accessToken === '') {
            return;
        }

        $message = FcmMessage::forNotificationSync($action, $notificationId);

        foreach ($tokens as $token) {
            $this->sendToToken($token, $message, $accessToken);
        }
    }

    private function sendToToken(
        string $token,
        FcmMessage $message,
        string $accessToken,
        ?string $platform = null,
    ): void {
        try {
            $messagePayload = [
                'token' => $token,
                'data' => $message->data,
                'android' => [
                    'priority' => 'high',
                ],
                'apns' => [
                    'payload' => [
                        'aps' => [
                            'content-available' => 1,
                        ],
                    ],
                ],
            ];

            if (! $message->dataOnly && ! $this->shouldUseLocalNotifications($platform)) {
                $messagePayload['notification'] = [
                    'title' => $message->title,
                    'body' => $message->body,
                ];
                $messagePayload['android']['notification'] = [
                    'sound' => 'default',
                ];
                $messagePayload['apns']['payload']['aps']['sound'] = 'default';
                $messagePayload['webpush'] = [
                    'notification' => [
                        'title' => $message->title,
                        'body' => $message->body,
                    ],
                    'fcm_options' => [
                        'link' => $message->data['url'] ?? config('app.url'),
                    ],
                ];
            }

            $response = Http::withToken($accessToken)
                ->timeout(15)
                ->post($this->endpoint(), [
                    'message' => $messagePayload,
                ]);

            if ($response->successful()) {
                return;
            }

            if ($this->isInvalidTokenResponse($response->json())) {
                FcmToken::query()->where('token', $token)->delete();
            }

            report(new \RuntimeException('Penghantaran FCM gagal: '.$response->body()));
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function shouldUseLocalNotifications(?string $platform): bool
    {
        if (! is_string($platform) || $platform === '') {
            return false;
        }

        return in_array($platform, ['android-webview', 'flutter-android-webview'], true);
    }

    private function endpoint(): string
    {
        return sprintf(
            'https://fcm.googleapis.com/v1/projects/%s/messages:send',
            config('services.firebase.project_id')
        );
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    private function isInvalidTokenResponse(?array $payload): bool
    {
        $details = $payload['error']['details'] ?? [];

        if (! is_array($details)) {
            return false;
        }

        foreach ($details as $detail) {
            if (! is_array($detail)) {
                continue;
            }

            $errorCode = $detail['errorCode'] ?? null;
            if (in_array($errorCode, ['UNREGISTERED', 'INVALID_ARGUMENT'], true)) {
                return true;
            }
        }

        return false;
    }

    private function supportsFcmRouting(object $notifiable): bool
    {
        return method_exists($notifiable, 'routeNotificationForFcm')
            && Schema::hasTable((new FcmToken())->getTable());
    }
}
