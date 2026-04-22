<?php

namespace Tests\Unit\Support;

use App\Models\User;
use App\Support\WebViewAuthPayload;
use PHPUnit\Framework\TestCase;

class WebViewAuthPayloadTest extends TestCase
{
    public function test_it_builds_a_webview_payload_with_the_logged_in_username(): void
    {
        $user = new User([
            'id' => 42,
            'name' => 'Guru Webview',
            'nama_samaran' => 'Cikgu Webview',
            'email' => 'guru@app.test',
        ]);
        $user->id = 42;

        $payload = WebViewAuthPayload::fromUser($user);

        $this->assertSame([
            'id' => 42,
            'username' => 'guru@app.test',
            'display_name' => 'Cikgu Webview',
            'email' => 'guru@app.test',
        ], $payload);
    }
}
