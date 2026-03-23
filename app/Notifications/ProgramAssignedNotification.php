<?php

namespace App\Notifications;

use App\Models\Program;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ProgramAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Program $program,
        private readonly User $actor,
        private readonly string $action = 'ditambah',
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
        $programDate = optional($this->program->program_date)->format('d/m/Y') ?? '-';
        $programTime = optional($this->program->program_time)->format('H:i') ?? '-';
        $location = $this->program->location ?: '-';

        return [
            'program_id' => $this->program->id,
            'guru_name' => $notifiable->display_name ?? '-',
            'guru_avatar_url' => $notifiable->avatar_url ?? '/images/default-avatar.svg',
            'program_title' => $this->program->title,
            'program_date' => $programDate,
            'notification_title' => 'Program '.ucfirst($this->action),
            'notification_meta' => $this->program->title.' | '.$programDate,
            'notification_message' => 'Anda '.$this->action.' ke program ini pada '.$programDate.' '.$programTime.' di '.$location.'.',
            'url' => route('programs.show', $this->program),
            'changed_by' => $this->actor->display_name,
        ];
    }
}

