<?php

namespace App\Notifications;

class FcmMessage
{
    /**
     * @param  array<string, string>  $data
     */
    public function __construct(
        public readonly string $title,
        public readonly string $body,
        public readonly array $data = [],
        public readonly bool $dataOnly = false,
    ) {
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromDatabaseNotificationData(array $payload, string $notificationType, ?string $notificationId = null): self
    {
        $data = [];

        foreach ($payload as $key => $value) {
            if (is_null($value)) {
                continue;
            }

            if (is_scalar($value)) {
                $data[$key] = (string) $value;
                continue;
            }

            $data[$key] = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
        }

        $data['notification_type'] = $notificationType;
        $data['notification_id'] = $notificationId ?? '';
        $data['sync_action'] = 'create';

        return new self(
            title: (string) ($payload['notification_title'] ?? 'Notifikasi baru'),
            body: (string) ($payload['notification_message'] ?? ''),
            data: $data,
        );
    }

    public static function forNotificationSync(string $action, string $notificationId): self
    {
        return new self(
            title: '',
            body: '',
            data: [
                'notification_id' => $notificationId,
                'sync_action' => $action,
            ],
            dataOnly: true,
        );
    }
}
