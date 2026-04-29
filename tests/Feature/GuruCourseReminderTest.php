<?php

namespace Tests\Feature;

use App\Http\Controllers\GuruCourseController;
use App\Models\Guru;
use App\Models\GuruCourseOffer;
use App\Models\GuruCourseOfferResponse;
use App\Models\User;
use App\Services\N8nWebhookService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GuruCourseReminderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('nama_samaran')->nullable();
            $table->string('email')->unique();
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });

        Schema::create('model_has_roles', function (Blueprint $table): void {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
        });

        Schema::create('gurus', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('pasti_id')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('kursus_guru')->nullable();
            $table->boolean('is_assistant')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('guru_course_offers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedTinyInteger('target_semester');
            $table->date('registration_deadline');
            $table->unsignedBigInteger('sent_by')->nullable();
            $table->timestamp('sent_at');
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('guru_course_offer_responses', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('guru_course_offer_id');
            $table->unsignedBigInteger('guru_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('decision', 20)->nullable();
            $table->text('stop_reason')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
        });

        \DB::table('roles')->insert([
            ['name' => 'master_admin', 'guard_name' => 'web'],
            ['name' => 'admin', 'guard_name' => 'web'],
            ['name' => 'guru', 'guard_name' => 'web'],
        ]);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('guru_course_offer_responses');
        Schema::dropIfExists('guru_course_offers');
        Schema::dropIfExists('gurus');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_request_pending_responses_sends_numbered_guru_list_without_test_account(): void
    {
        $this->seedPendingGuruCourseResponses();

        $webhookService = \Mockery::mock(N8nWebhookService::class);
        $webhookService->shouldReceive('toActionUrl')
            ->once()
            ->with(\Mockery::on(fn ($url) => is_string($url) && str_contains($url, '/kursus-guru')))
            ->andReturn('https://example.test/kursus-guru');
        $webhookService->shouldReceive('sendByTemplate')
            ->once()
            ->with(
                N8nWebhookService::KEY_TEXT_GURU_COURSE_RESPONSE_REMINDER,
                ['senarai_guru' => "1- Ahmad\n2- Nurul\n3- Siti"],
                'https://example.test/kursus-guru'
            );

        $this->app->instance(N8nWebhookService::class, $webhookService);

        $request = Request::create('/kursus-guru/request-reminder', 'POST');
        $request->setUserResolver(fn (): User => $this->masterAdmin());

        $response = app(GuruCourseController::class)->requestPendingResponses($request);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('Mesej telah berjaya dihantar ke group guru.', $response->getSession()->get('status'));
    }

    public function test_responding_last_real_guru_course_request_sends_auto_thanks_even_with_test_pending(): void
    {
        $payload = $this->seedCompletedGuruCourseResponsesExceptTest();

        $webhookService = \Mockery::mock(N8nWebhookService::class);
        $webhookService->shouldReceive('toActionUrl')
            ->once()
            ->with(\Mockery::on(fn ($url) => is_string($url) && str_contains($url, '/kursus-guru')))
            ->andReturn('https://example.test/kursus-guru');
        $webhookService->shouldReceive('sendByTemplate')
            ->once()
            ->with(
                N8nWebhookService::KEY_TEXT_ALL_GURU_COMPLETED_THANKS,
                ['perkara' => 'respon sambung kursus guru'],
                'https://example.test/kursus-guru'
            );

        $this->app->instance(N8nWebhookService::class, $webhookService);

        $request = Request::create('/kursus-guru/' . $payload['response']->id . '/respond', 'POST', [
            'decision' => 'continue',
        ]);
        $request->setUserResolver(fn (): User => $payload['user']);

        $response = app(GuruCourseController::class)->respond($request, $payload['response']);

        $this->assertSame(302, $response->getStatusCode());
    }

    public function test_responding_last_latest_offer_sends_auto_thanks_even_when_old_offer_has_pending_response(): void
    {
        $payload = $this->seedLatestGuruCourseResponsesExceptTestWithOldPendingResponse();

        $webhookService = \Mockery::mock(N8nWebhookService::class);
        $webhookService->shouldReceive('toActionUrl')
            ->once()
            ->with(\Mockery::on(fn ($url) => is_string($url) && str_contains($url, '/kursus-guru')))
            ->andReturn('https://example.test/kursus-guru');
        $webhookService->shouldReceive('sendByTemplate')
            ->once()
            ->with(
                N8nWebhookService::KEY_TEXT_ALL_GURU_COMPLETED_THANKS,
                ['perkara' => 'respon sambung kursus guru'],
                'https://example.test/kursus-guru'
            );

        $this->app->instance(N8nWebhookService::class, $webhookService);

        $request = Request::create('/kursus-guru/' . $payload['response']->id . '/respond', 'POST', [
            'decision' => 'continue',
        ]);
        $request->setUserResolver(fn (): User => $payload['user']);

        $response = app(GuruCourseController::class)->respond($request, $payload['response']);

        $this->assertSame(302, $response->getStatusCode());
    }

    private function masterAdmin(): User
    {
        $user = User::query()->create([
            'name' => 'Master Admin',
            'nama_samaran' => 'Master Admin',
            'email' => 'master'.uniqid().'@example.test',
        ]);

        $roleId = (int) \DB::table('roles')->where('name', 'master_admin')->value('id');
        \DB::table('model_has_roles')->insert([
            'role_id' => $roleId,
            'model_type' => User::class,
            'model_id' => $user->id,
        ]);

        return $user;
    }

    private function seedPendingGuruCourseResponses(): void
    {
        $offer = GuruCourseOffer::query()->create([
            'target_semester' => 1,
            'registration_deadline' => now()->addWeek()->toDateString(),
            'sent_by' => null,
            'sent_at' => now(),
            'note' => null,
        ]);

        foreach (['Test', 'Ahmad', 'Siti', 'Nurul'] as $name) {
            $user = User::query()->create([
                'name' => $name,
                'nama_samaran' => $name,
                'email' => strtolower($name).uniqid().'@example.test',
            ]);

            $guru = Guru::query()->create([
                'user_id' => $user->id,
                'name' => $name,
                'email' => $user->email,
                'kursus_guru' => 'belum_kursus',
                'is_assistant' => false,
                'active' => true,
            ]);

            GuruCourseOfferResponse::query()->create([
                'guru_course_offer_id' => $offer->id,
                'guru_id' => $guru->id,
                'user_id' => $user->id,
                'decision' => null,
                'stop_reason' => null,
                'responded_at' => null,
            ]);
        }
    }

    private function seedCompletedGuruCourseResponsesExceptTest(): array
    {
        $offer = GuruCourseOffer::query()->create([
            'target_semester' => 1,
            'registration_deadline' => now()->addWeek()->toDateString(),
            'sent_by' => null,
            'sent_at' => now(),
            'note' => null,
        ]);

        foreach (['Test', 'Ahmad', 'Siti', 'Nurul'] as $index => $name) {
            $user = User::query()->create([
                'name' => $name,
                'nama_samaran' => $name,
                'email' => strtolower($name).uniqid().'@example.test',
            ]);
            $this->attachRole($user, 'guru');

            $guru = Guru::query()->create([
                'user_id' => $user->id,
                'name' => $name,
                'email' => $user->email,
                'kursus_guru' => 'belum_kursus',
                'is_assistant' => false,
                'active' => true,
            ]);

            GuruCourseOfferResponse::query()->create([
                'guru_course_offer_id' => $offer->id,
                'guru_id' => $guru->id,
                'user_id' => $user->id,
                'decision' => $index < 3 ? 'continue' : null,
                'stop_reason' => null,
                'responded_at' => $index < 3 ? now()->subMinutes(10 - $index) : null,
            ]);
        }

        $pendingUser = User::query()->where('name', 'Nurul')->firstOrFail();
        $pendingResponse = GuruCourseOfferResponse::query()
            ->whereHas('guru', fn ($query) => $query->where('name', 'Nurul'))
            ->firstOrFail();

        return [
            'user' => $pendingUser,
                'response' => $pendingResponse,
        ];
    }

    private function seedLatestGuruCourseResponsesExceptTestWithOldPendingResponse(): array
    {
        $oldOffer = GuruCourseOffer::query()->create([
            'target_semester' => 1,
            'registration_deadline' => now()->subWeek()->toDateString(),
            'sent_by' => null,
            'sent_at' => now()->subWeeks(2),
            'note' => null,
        ]);

        $latestOffer = GuruCourseOffer::query()->create([
            'target_semester' => 1,
            'registration_deadline' => now()->addWeek()->toDateString(),
            'sent_by' => null,
            'sent_at' => now(),
            'note' => null,
        ]);

        foreach (['Test', 'Ahmad', 'Siti', 'Nurul'] as $index => $name) {
            $user = User::query()->create([
                'name' => $name,
                'nama_samaran' => $name,
                'email' => strtolower($name).uniqid().'@example.test',
            ]);
            $this->attachRole($user, 'guru');

            $guru = Guru::query()->create([
                'user_id' => $user->id,
                'name' => $name,
                'email' => $user->email,
                'kursus_guru' => 'belum_kursus',
                'is_assistant' => false,
                'active' => true,
            ]);

            GuruCourseOfferResponse::query()->create([
                'guru_course_offer_id' => $oldOffer->id,
                'guru_id' => $guru->id,
                'user_id' => $user->id,
                'decision' => $name === 'Ahmad' ? null : 'continue',
                'stop_reason' => null,
                'responded_at' => $name === 'Ahmad' ? null : now()->subWeeks(2),
            ]);

            GuruCourseOfferResponse::query()->create([
                'guru_course_offer_id' => $latestOffer->id,
                'guru_id' => $guru->id,
                'user_id' => $user->id,
                'decision' => $index < 3 ? 'continue' : null,
                'stop_reason' => null,
                'responded_at' => $index < 3 ? now()->subMinutes(10 - $index) : null,
            ]);
        }

        $pendingUser = User::query()->where('name', 'Nurul')->firstOrFail();
        $pendingResponse = GuruCourseOfferResponse::query()
            ->where('guru_course_offer_id', $latestOffer->id)
            ->whereHas('guru', fn ($query) => $query->where('name', 'Nurul'))
            ->firstOrFail();

        return [
            'user' => $pendingUser,
            'response' => $pendingResponse,
        ];
    }

    private function attachRole(User $user, string $roleName): void
    {
        $roleId = (int) \DB::table('roles')->where('name', $roleName)->value('id');
        \DB::table('model_has_roles')->insert([
            'role_id' => $roleId,
            'model_type' => User::class,
            'model_id' => $user->id,
        ]);
    }
}
