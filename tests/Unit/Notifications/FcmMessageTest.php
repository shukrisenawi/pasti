<?php

namespace Tests\Unit\Notifications;

use App\Notifications\FcmMessage;
use PHPUnit\Framework\TestCase;

class FcmMessageTest extends TestCase
{
    public function test_it_builds_a_create_message_from_database_notification_data(): void
    {
        $message = FcmMessage::fromDatabaseNotificationData([
            'notification_title' => 'Notifikasi Baru',
            'notification_message' => 'Ada claim baru.',
            'url' => '/claims',
        ], 'App\\Notifications\\ClaimSubmittedNotification', 'notif-1');

        $this->assertSame('Notifikasi Baru', $message->title);
        $this->assertSame('Ada claim baru.', $message->body);
        $this->assertSame('notif-1', $message->data['notification_id']);
        $this->assertSame('create', $message->data['sync_action']);
        $this->assertFalse($message->dataOnly);
    }

    public function test_it_builds_a_data_only_read_sync_message(): void
    {
        $message = FcmMessage::forNotificationSync('read', 'notif-2');

        $this->assertTrue($message->dataOnly);
        $this->assertSame('read', $message->data['sync_action']);
        $this->assertSame('notif-2', $message->data['notification_id']);
    }
}
