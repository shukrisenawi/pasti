<?php

namespace App\Notifications;

use App\Models\GuruSalaryRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class GuruSalaryRequestedNotification extends Notification
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
        $pastiName = $this->salaryRequest->guru?->pasti?->name ?? '-';

        return [
            'guru_salary_request_id' => $this->salaryRequest->id,
            'guru_id' => $this->salaryRequest->guru_id,
            'pasti_name' => $pastiName,
            'notification_title' => 'Permintaan maklumat gaji guru',
            'notification_meta' => $pastiName . ' | Kemas kini gaji dan elaun semasa',
            'notification_message' => 'Sila isi maklumat gaji dan elaun semasa anda.',
            'url' => route('guru-salary-information.index'),
        ];
    }
}

