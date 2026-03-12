<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PemarkahanSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $titleOptionName,
        private readonly int $year,
        private readonly float $score,
        private readonly string $pastiName,
    ) {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $scoreText = number_format($this->score, 2);

        return [
            'notification_title' => 'Permarkahan baru dikemaskini',
            'notification_meta' => $this->pastiName . ' | ' . $this->year,
            'notification_message' => $this->titleOptionName . ': ' . $scoreText,
            'pasti_name' => $this->pastiName,
            'score_title' => $this->titleOptionName,
            'score_year' => $this->year,
            'score_value' => $scoreText,
            'url' => route('pemarkahan.index'),
        ];
    }
}
