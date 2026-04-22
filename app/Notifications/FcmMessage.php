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
    ) {
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromDatabaseNotificationData(array $payload, string $notificationType): self
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

        return new self(
            title: (string) ($payload['notification_title'] ?? 'Notifikasi baru'),
            body: (string) ($payload['notification_message'] ?? ''),
            data: $data,
        );
    }
}
