<?php

namespace Tests\Unit\Notifications;

use App\Listeners\SendDatabaseNotificationToFcm;
use App\Models\User;
use App\Notifications\FcmMessage;
use App\Services\FcmNotificationService;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Notifications\Notification;
use Mockery;
use PHPUnit\Framework\TestCase;

class SendDatabaseNotificationToFcmTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_it_forwards_database_notifications_to_fcm(): void
    {
        $user = new User([
            'name' => 'Guru Ujian',
            'email' => 'guru@ujian.test',
        ]);

        $notification = new class extends Notification
        {
            public function toArray(object $notifiable): array
            {
                return [
                    'notification_title' => 'Tajuk Ujian',
                    'notification_message' => 'Mesej ujian terus ke FCM.',
                    'url' => '/claims',
                    'claim_id' => 99,
                ];
            }
        };

        $service = Mockery::mock(FcmNotificationService::class);
        $service->shouldReceive('sendToNotifiable')
            ->once()
            ->withArgs(function (User $notifiable, FcmMessage $message, Notification $sentNotification): bool {
                return $notifiable->email === 'guru@ujian.test'
                    && $message->title === 'Tajuk Ujian'
                    && $message->body === 'Mesej ujian terus ke FCM.'
                    && $message->data['url'] === '/claims'
                    && $message->data['claim_id'] === '99'
                    && $sentNotification instanceof Notification;
            });

        $listener = new SendDatabaseNotificationToFcm($service);
        $listener->handle(new NotificationSent($user, $notification, 'database'));

        $this->addToAssertionCount(1);
    }

    public function test_it_ignores_non_database_channels(): void
    {
        $service = Mockery::mock(FcmNotificationService::class);
        $service->shouldNotReceive('sendToNotifiable');

        $listener = new SendDatabaseNotificationToFcm($service);
        $listener->handle(new NotificationSent(new User(), new class extends Notification
        {
        }, 'mail'));

        $this->addToAssertionCount(1);
    }
}
