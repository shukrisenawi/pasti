<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AjkPositionUpdatedNotification extends Notification
{
    use Queueable;

    /**
     * @param  array<int, string>  $addedPositions
     * @param  array<int, string>  $removedPositions
     */
    public function __construct(
        private readonly User $changedBy,
        private readonly array $addedPositions,
        private readonly array $removedPositions,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $changes = [];

        if ($this->addedPositions !== []) {
            $changes[] = 'Ditambah: '.implode(', ', $this->addedPositions);
        }

        if ($this->removedPositions !== []) {
            $changes[] = 'Dibuang: '.implode(', ', $this->removedPositions);
        }

        return [
            'guru_name' => $this->changedBy->display_name,
            'guru_avatar_url' => $this->changedBy->avatar_url ?? '/images/default-avatar.svg',
            'notification_title' => 'Kemaskini jawatan AJK Program',
            'notification_meta' => 'Dikemaskini oleh '.$this->changedBy->display_name,
            'notification_message' => implode(' | ', $changes),
            'url' => route('dashboard'),
        ];
    }
}

