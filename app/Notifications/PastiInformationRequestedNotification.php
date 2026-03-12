<?php

namespace App\Notifications;

use App\Models\PastiInformationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PastiInformationRequestedNotification extends Notification
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
        return [
            'pasti_information_request_id' => $this->infoRequest->id,
            'pasti_id' => $this->infoRequest->pasti_id,
            'pasti_name' => $this->infoRequest->pasti?->name ?? '-',
            'notification_title' => 'Permintaan maklumat PASTI',
            'notification_meta' => ($this->infoRequest->pasti?->name ?? '-') . ' | Kemas kini data semasa',
            'notification_message' => 'Salah seorang guru perlu isi jumlah guru dan murid terkini.',
            'url' => route('pasti-information.index'),
        ];
    }
}
