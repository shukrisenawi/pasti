<?php

namespace App\Notifications;

use App\Models\ProgramParticipation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ProgramAbsenceReasonSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly ProgramParticipation $participation)
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
            'program_id' => $this->participation->program_id,
            'guru_id' => $this->participation->guru?->id,
            'guru_name' => $this->participation->guru?->display_name ?? '-',
            'guru_avatar_url' => $this->participation->guru?->avatar_url ?? '/images/default-avatar.svg',
            'pasti_name' => $this->participation->program?->pasti?->name ?? '-',
            'program_title' => $this->participation->program?->title ?? '-',
            'program_date' => optional($this->participation->program?->program_date)->format('d/m/Y'),
            'reason' => $this->participation->absence_reason,
            'notification_title' => 'Alasan ketidakhadiran program',
            'notification_meta' => ($this->participation->guru?->display_name ?? '-') . ' · ' . ($this->participation->program?->title ?? '-'),
            'notification_message' => $this->participation->absence_reason,
            'url' => route('programs.show', $this->participation->program_id),
        ];
    }
}