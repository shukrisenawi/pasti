<?php

namespace App\Notifications;

use App\Models\PastiInformationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PastiInformationUpdatedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly PastiInformationRequest $infoRequest)
    {
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
        $guruName = $this->infoRequest->completedBy?->display_name ?? '-';
        $pastiName = $this->infoRequest->pasti?->name ?? '-';

        return [
            'pasti_information_request_id' => $this->infoRequest->id,
            'guru_name' => $guruName,
            'guru_avatar_url' => $this->infoRequest->completedBy?->avatar_url ?? '/images/default-avatar.svg',
            'pasti_name' => $pastiName,
            'notification_title' => 'Maklumat PASTI dikemaskini',
            'notification_meta' => $guruName . ' · ' . $pastiName,
            'notification_message' => 'Data jumlah guru dan murid terkini telah dikemaskini.',
            'url' => route('pasti-information.index'),
        ];
    }
}
