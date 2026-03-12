<?php

namespace App\Notifications;

use App\Models\LeaveNotice;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LeaveNoticeSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly LeaveNotice $leaveNotice)
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
            'leave_notice_id' => $this->leaveNotice->id,
            'guru_id' => $this->leaveNotice->guru?->id,
            'guru_name' => $this->leaveNotice->guru?->display_name ?? '-',
            'guru_avatar_url' => $this->leaveNotice->guru?->avatar_url ?? '/images/default-avatar.svg',
            'pasti_name' => $this->leaveNotice->guru?->pasti?->name ?? '-',
            'leave_date' => optional($this->leaveNotice->leave_date)->format('d/m/Y'),
            'reason' => $this->leaveNotice->reason,
            'notification_title' => 'Permohonan cuti baru',
            'notification_meta' => ($this->leaveNotice->guru?->display_name ?? '-') . ' · ' . ($this->leaveNotice->guru?->pasti?->name ?? '-'),
            'notification_message' => $this->leaveNotice->reason,
            'url' => route('leave-notices.index'),
        ];
    }
}