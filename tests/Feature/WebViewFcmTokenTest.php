<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureGuruWebOnboardingCompleted;
use App\Models\FcmToken;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WebViewFcmTokenTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('fcm_tokens', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->text('token')->unique();
            $table->string('device_name')->nullable();
            $table->string('platform', 50)->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('fcm_tokens');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_authenticated_user_can_register_a_webview_fcm_token(): void
    {
        $this->withoutMiddleware(EnsureGuruWebOnboardingCompleted::class);

        $user = User::query()->create([
            'name' => 'Guru Ujian',
            'email' => 'guru@app.test',
            'password' => 'hashed-password',
        ]);

        $response = $this->actingAs($user)->post('/mobile/fcm-token', [
            'fcm_token' => 'token-abc',
            'device_name' => 'PASTI Android',
            'platform' => 'android-webview',
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'message' => 'Token FCM berjaya didaftarkan.',
            ]);

        $this->assertDatabaseHas('fcm_tokens', [
            'user_id' => $user->id,
            'token' => 'token-abc',
            'device_name' => 'PASTI Android',
            'platform' => 'android-webview',
        ]);
    }

    public function test_fcm_token_can_be_deleted_without_an_authenticated_session(): void
    {
        $user = User::query()->create([
            'name' => 'Guru Ujian',
            'email' => 'guru2@app.test',
            'password' => 'hashed-password',
        ]);

        FcmToken::query()->create([
            'user_id' => $user->id,
            'token' => 'token-buang',
            'device_name' => 'PASTI Android',
            'platform' => 'android-webview',
        ]);

        $response = $this->delete('/mobile/fcm-token', [
            'fcm_token' => 'token-buang',
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'message' => 'Token FCM berjaya dibuang.',
            ]);

        $this->assertDatabaseMissing('fcm_tokens', [
            'token' => 'token-buang',
        ]);
    }
}
