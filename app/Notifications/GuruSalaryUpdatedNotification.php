<?php

namespace App\Notifications;

use App\Models\GuruSalaryRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class GuruSalaryUpdatedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly GuruSalaryRequest $salaryRequest)
    {
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
        $guruName = $this->salaryRequest->completedBy?->display_name ?? '-';
        $pastiName = $this->salaryRequest->guru?->pasti?->name ?? '-';

        return [
            'guru_salary_request_id' => $this->salaryRequest->id,
            'guru_name' => $guruName,
            'guru_avatar_url' => $this->salaryRequest->completedBy?->avatar_url ?? '/images/default-avatar.svg',
            'pasti_name' => $pastiName,
            'notification_title' => 'Maklumat gaji guru dikemaskini',
            'notification_meta' => $guruName . ' · ' . $pastiName,
            'notification_message' => 'Maklumat gaji dan elaun semasa telah dihantar.',
            'url' => route('guru-salary-information.index'),
        ];
    }
}

