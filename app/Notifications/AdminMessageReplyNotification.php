<?php

namespace App\Notifications;

use App\Models\AdminMessage;
use App\Models\AdminMessageReply;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class AdminMessageReplyNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly AdminMessage $message,
        private readonly AdminMessageReply $reply,
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
        $sender = $this->reply->sender;

        return [
            'admin_message_id' => $this->message->id,
            'guru_name' => $sender?->display_name ?? '-',
            'guru_avatar_url' => $sender?->avatar_url ?? '/images/default-avatar.svg',
            'notification_title' => 'Balasan mesej',
            'notification_meta' => ($sender?->display_name ?? '-') . ' · ' . ($this->message->title ?? '-'),
            'notification_message' => Str::limit($this->reply->body, 90),
            'url' => route('messages.show', $this->message),
        ];
    }
}
