<?php

namespace Tests\Feature;

use App\Models\AdminMessage;
use App\Models\AdminMessageReply;
use App\Models\User;
use App\Notifications\AdminMessageReceivedNotification;
use App\Notifications\AdminMessageReplyNotification;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MessageNotificationFcmGateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('nama_samaran')->nullable();
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('admin_messages', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('sender_id');
            $table->string('title');
            $table->text('body');
            $table->string('image_path')->nullable();
            $table->boolean('sent_to_all')->default(false);
            $table->timestamps();
        });

        Schema::create('admin_message_replies', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('admin_message_id');
            $table->unsignedBigInteger('sender_id');
            $table->text('body');
            $table->string('image_path')->nullable();
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('admin_message_replies');
        Schema::dropIfExists('admin_messages');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_received_notification_skips_fcm_when_previous_message_notification_is_unread(): void
    {
        $sender = User::query()->create([
            'name' => 'Admin',
            'nama_samaran' => 'Admin',
            'email' => 'admin@test.local',
        ]);
        $recipient = User::query()->create([
            'name' => 'Guru',
            'nama_samaran' => 'Guru',
            'email' => 'guru@test.local',
        ]);

        $message = AdminMessage::query()->create([
            'sender_id' => $sender->id,
            'title' => 'Hebahan kepada semua guru',
            'body' => 'Mesej awal',
            'sent_to_all' => true,
        ]);

        DatabaseNotification::query()->create([
            'id' => 'notif-lama-1',
            'type' => AdminMessageReceivedNotification::class,
            'notifiable_type' => User::class,
            'notifiable_id' => $recipient->id,
            'data' => [
                'admin_message_id' => $message->id,
            ],
        ]);

        $notification = new AdminMessageReceivedNotification($message->load('sender'));

        $this->assertFalse($notification->shouldSendFcmForDatabase($recipient, 'notif-baru-1'));
    }

    public function test_reply_notification_allows_fcm_after_previous_message_notifications_are_read(): void
    {
        $sender = User::query()->create([
            'name' => 'Admin',
            'nama_samaran' => 'Admin',
            'email' => 'admin2@test.local',
        ]);
        $recipient = User::query()->create([
            'name' => 'Guru',
            'nama_samaran' => 'Guru',
            'email' => 'guru2@test.local',
        ]);

        $message = AdminMessage::query()->create([
            'sender_id' => $sender->id,
            'title' => 'Hebahan kepada semua guru',
            'body' => 'Mesej awal',
            'sent_to_all' => true,
        ]);

        $reply = AdminMessageReply::query()->create([
            'admin_message_id' => $message->id,
            'sender_id' => $sender->id,
            'body' => 'Balasan terkini',
        ]);

        DatabaseNotification::query()->create([
            'id' => 'notif-lama-2',
            'type' => AdminMessageReplyNotification::class,
            'notifiable_type' => User::class,
            'notifiable_id' => $recipient->id,
            'data' => [
                'admin_message_id' => $message->id,
            ],
            'read_at' => now(),
        ]);

        $notification = new AdminMessageReplyNotification($message->load('sender'), $reply->load('sender'));

        $this->assertTrue($notification->shouldSendFcmForDatabase($recipient, 'notif-baru-2'));
    }
}
