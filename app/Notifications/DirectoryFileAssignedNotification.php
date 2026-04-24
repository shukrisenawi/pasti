<?php

namespace App\Notifications;

use App\Models\DirectoryFile;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DirectoryFileAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly DirectoryFile $directoryFile,
        private readonly User $uploadedBy,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'directory_file_id' => $this->directoryFile->id,
            'guru_name' => $this->uploadedBy->display_name,
            'guru_avatar_url' => $this->uploadedBy->avatar_url ?? '/images/default-avatar.svg',
            'notification_title' => 'Fail Directory Baru',
            'notification_meta' => 'Dihantar oleh ' . $this->uploadedBy->display_name,
            'notification_message' => 'Fail "' . $this->directoryFile->title . '" telah dimuat naik untuk anda.',
            'url' => route('directory-files.index'),
        ];
    }
}

