<?php

namespace Tests\Unit\Models;

use App\Models\FcmToken;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\TestCase;

class UserFcmRoutingTest extends TestCase
{
    public function test_it_returns_registered_fcm_tokens_for_notification_routing(): void
    {
        $user = new User();
        $user->setRelation('fcmTokens', new Collection([
            new FcmToken(['token' => 'token-satu']),
            new FcmToken(['token' => 'token-dua']),
        ]));

        $this->assertSame(['token-satu', 'token-dua'], $user->routeNotificationForFcm());
    }
}
