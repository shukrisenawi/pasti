<?php

namespace App\Notifications;

use App\Models\Claim;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ClaimApprovedNotification extends Notification
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
            'notification_title' => 'Claim diluluskan',
            'notification_meta' => 'Jumlah diluluskan: RM ' . number_format((float) ($this->claim->approved_amount ?? 0), 2),
            'notification_message' => 'Pembayaran: ' . (($this->claim->payment_method ?? 'transfer') === 'cash' ? 'Tunai' : 'Transfer'),
            'url' => route('claims.index'),
        ];
    }
}

