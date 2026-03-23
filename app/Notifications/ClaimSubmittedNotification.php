<?php

namespace App\Notifications;

use App\Models\Claim;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ClaimSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Claim $claim)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'claim_id' => $this->claim->id,
            'guru_name' => $this->claim->user?->display_name ?? '-',
            'guru_avatar_url' => $this->claim->user?->avatar_url ?? '/images/default-avatar.svg',
            'pasti_name' => $this->claim->pasti?->name ?? '-',
            'notification_title' => 'Claim baru dihantar',
            'notification_meta' => ($this->claim->user?->display_name ?? '-') . ' · ' . ($this->claim->pasti?->name ?? '-'),
            'notification_message' => 'Jumlah RM ' . number_format((float) $this->claim->amount, 2),
            'url' => route('claims.index', ['tab' => 'pending']),
        ];
    }
}

