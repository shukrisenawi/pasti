<?php

namespace Tests\Unit\Notifications;

use App\Listeners\SendDatabaseNotificationToFcm;
use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
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
                    && $message->data['notification_id'] === 'notif-123'
                    && $message->data['sync_action'] === 'create'
                    && $sentNotification instanceof Notification;
            });

        $listener = new SendDatabaseNotificationToFcm($service);
        $listener->handle(new NotificationSent(
            $user,
            $notification,
            'database',
            new DatabaseNotification(['id' => 'notif-123'])
        ));

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

    public function test_it_skips_fcm_when_notification_requests_suppression(): void
    {
        $service = Mockery::mock(FcmNotificationService::class);
        $service->shouldNotReceive('sendToNotifiable');

        $listener = new SendDatabaseNotificationToFcm($service);
        $listener->handle(new NotificationSent(
            new User(['email' => 'guru@ujian.test']),
            new class extends Notification
            {
                public function toArray(object $notifiable): array
                {
                    return [
                        'notification_title' => 'Mesej baru',
                        'notification_message' => 'Perlu senyap.',
                    ];
                }

                public function shouldSendFcmForDatabase(object $notifiable, ?string $notificationId): bool
                {
                    return false;
                }
            },
            'database',
            new DatabaseNotification(['id' => 'notif-999'])
        ));

        $this->addToAssertionCount(1);
    }

    public function test_it_does_not_throw_when_fcm_service_fails(): void
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
                    'url' => '/messages/2',
                ];
            }
        };

        $service = Mockery::mock(FcmNotificationService::class);
        $service->shouldReceive('sendToNotifiable')
            ->once()
            ->andThrow(new \RuntimeException('FCM down'));

        $listener = new SendDatabaseNotificationToFcm($service);

        $listener->handle(new NotificationSent(
            $user,
            $notification,
            'database',
            new DatabaseNotification(['id' => 'notif-500'])
        ));

        $this->addToAssertionCount(1);
    }
}
