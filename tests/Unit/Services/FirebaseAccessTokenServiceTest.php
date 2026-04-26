<?php

namespace Tests\Unit\Services;

use App\Services\FirebaseAccessTokenService;
use ErrorException;
use Tests\TestCase;

class FirebaseAccessTokenServiceTest extends TestCase
{
    public function test_it_uses_inline_service_account_json_when_available(): void
    {
        config()->set('services.firebase.project_id', 'demo-project');
        config()->set('services.firebase.service_account_json', json_encode([
            'client_email' => 'firebase@example.test',
            'private_key' => "-----BEGIN PRIVATE KEY-----\nabc123\n-----END PRIVATE KEY-----\n",
        ]));
        config()->set('services.firebase.service_account_path', null);

        $service = app(FirebaseAccessTokenService::class);

        $this->assertTrue($service->isConfigured());
    }

    public function test_it_treats_unreadable_service_account_path_as_not_configured(): void
    {
        config()->set('services.firebase.project_id', 'demo-project');
        config()->set('services.firebase.service_account_json', null);
        config()->set('services.firebase.service_account_path', 'invalid://firebase-service-account.json');

        $service = app(FirebaseAccessTokenService::class);

        set_error_handler(static function (int $severity, string $message, string $file, int $line): never {
            throw new ErrorException($message, 0, $severity, $file, $line);
        });

        try {
            $this->assertFalse($service->isConfigured());
        } finally {
            restore_error_handler();
        }
    }
}
