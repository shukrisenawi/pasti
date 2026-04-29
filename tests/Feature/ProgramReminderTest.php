<?php

namespace Tests\Feature;

use App\Http\Controllers\ProgramController;
use App\Models\Guru;
use App\Models\Kawasan;
use App\Models\Pasti;
use App\Models\Program;
use App\Models\ProgramStatus;
use App\Models\User;
use App\Services\KpiCalculationService;
use App\Services\N8nWebhookService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProgramReminderTest extends TestCase
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

        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('kawasans', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('pastis', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('kawasan_id')->nullable();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('admin_pasti', function (Blueprint $table): void {
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('pasti_id');
        });

        Schema::create('gurus', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('pasti_id')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_assistant')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('programs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('pasti_id')->nullable();
            $table->string('title');
            $table->date('program_date');
            $table->time('program_time')->nullable();
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->boolean('require_absence_reason')->default(false);
            $table->unsignedInteger('markah')->default(1);
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
        });

        Schema::create('program_teacher', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('program_id');
            $table->unsignedBigInteger('guru_id');
            $table->unsignedBigInteger('program_status_id')->nullable();
            $table->text('absence_reason')->nullable();
            $table->string('absence_reason_status')->nullable();
            $table->unsignedBigInteger('absence_reason_reviewed_by')->nullable();
            $table->timestamp('absence_reason_reviewed_at')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('program_statuses', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->boolean('is_hadir')->default(false);
            $table->timestamps();
        });

        \DB::table('roles')->insert([
            ['name' => 'master_admin', 'guard_name' => 'web'],
            ['name' => 'admin', 'guard_name' => 'web'],
            ['name' => 'guru', 'guard_name' => 'web'],
        ]);

        ProgramStatus::query()->insert([
            ['name' => 'Hadir', 'code' => 'HADIR', 'is_hadir' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Tidak Hadir', 'code' => 'TIDAK_HADIR', 'is_hadir' => false, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('program_teacher');
        Schema::dropIfExists('program_statuses');
        Schema::dropIfExists('programs');
        Schema::dropIfExists('gurus');
        Schema::dropIfExists('pastis');
        Schema::dropIfExists('admin_pasti');
        Schema::dropIfExists('kawasans');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_request_pending_responses_sends_numbered_program_guru_list_without_test_account(): void
    {
        $program = $this->seedProgramWithPendingGurus();

        $webhookService = \Mockery::mock(N8nWebhookService::class);
        $webhookService->shouldReceive('toActionUrl')
            ->once()
            ->with(\Mockery::on(fn ($url) => is_string($url) && str_contains($url, '/programs/' . $program->id)))
            ->andReturn('https://example.test/programs/' . $program->id);
        $webhookService->shouldReceive('sendByTemplate')
            ->once()
            ->with(
                N8nWebhookService::KEY_TEXT_PROGRAM_RESPONSE_REMINDER,
                [
                    'program_title' => 'Program Ujian',
                    'senarai_guru' => "1- Ahmad\n2- Nurul\n3- Siti",
                ],
                'https://example.test/programs/' . $program->id
            );

        $this->app->instance(N8nWebhookService::class, $webhookService);

        $request = Request::create('/programs/' . $program->id . '/request-reminder', 'POST');
        $request->setUserResolver(fn (): User => $this->masterAdmin());

        $response = app(ProgramController::class)->requestPendingResponses($request, $program);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('Mesej telah berjaya dihantar ke group guru.', $response->getSession()->get('status'));
    }

    public function test_updating_last_real_program_participation_sends_auto_thanks_even_with_test_pending(): void
    {
        $payload = $this->seedProgramWithCompletedGurusExceptTest();

        $webhookService = \Mockery::mock(N8nWebhookService::class);
        $webhookService->shouldReceive('toActionUrl')
            ->once()
            ->with(\Mockery::on(fn ($url) => is_string($url) && str_contains($url, '/programs/' . $payload['program']->id)))
            ->andReturn('https://example.test/programs/' . $payload['program']->id);
        $webhookService->shouldReceive('sendByTemplate')
            ->once()
            ->with(
                N8nWebhookService::KEY_TEXT_ALL_GURU_COMPLETED_THANKS,
                ['perkara' => 'status program'],
                'https://example.test/programs/' . $payload['program']->id
            );

        $this->app->instance(N8nWebhookService::class, $webhookService);
        $kpiService = \Mockery::mock(KpiCalculationService::class);
        $kpiService->shouldReceive('recalculateForGuru')->once();
        $this->app->instance(KpiCalculationService::class, $kpiService);

        $request = Request::create('/programs/' . $payload['program']->id . '/guru/' . $payload['guruId'] . '/status', 'POST', [
            'program_status_id' => $payload['hadirStatusId'],
        ]);
        $request->setUserResolver(fn (): User => $payload['user']);

        $response = app(\App\Http\Controllers\ProgramParticipationController::class)->updateStatus($request, $payload['program'], $payload['guruId']);

        $this->assertSame(302, $response->getStatusCode());
    }

    public function test_repeated_updates_after_program_is_complete_do_not_resend_auto_thanks(): void
    {
        $payload = $this->seedProgramWithCompletedGurusExceptTest();

        $webhookService = \Mockery::mock(N8nWebhookService::class);
        $webhookService->shouldReceive('toActionUrl')
            ->once()
            ->with(\Mockery::on(fn ($url) => is_string($url) && str_contains($url, '/programs/' . $payload['program']->id)))
            ->andReturn('https://example.test/programs/' . $payload['program']->id);
        $webhookService->shouldReceive('sendByTemplate')
            ->once()
            ->with(
                N8nWebhookService::KEY_TEXT_ALL_GURU_COMPLETED_THANKS,
                ['perkara' => 'status program'],
                'https://example.test/programs/' . $payload['program']->id
            );
        $this->app->instance(N8nWebhookService::class, $webhookService);
        $kpiService = \Mockery::mock(KpiCalculationService::class);
        $kpiService->shouldReceive('recalculateForGuru')->twice();
        $this->app->instance(KpiCalculationService::class, $kpiService);

        $request = Request::create('/programs/' . $payload['program']->id . '/guru/' . $payload['guruId'] . '/status', 'POST', [
            'program_status_id' => $payload['hadirStatusId'],
        ]);
        $request->setUserResolver(fn (): User => $payload['user']);

        $controller = app(\App\Http\Controllers\ProgramParticipationController::class);

        $firstResponse = $controller->updateStatus($request, $payload['program'], $payload['guruId']);
        $secondResponse = $controller->updateStatus($request, $payload['program'], $payload['guruId']);

        $this->assertSame(302, $firstResponse->getStatusCode());
        $this->assertSame(302, $secondResponse->getStatusCode());
    }

    public function test_pending_absence_review_does_not_send_auto_thanks_until_admin_completes_review(): void
    {
        $payload = $this->seedProgramAwaitingAbsenceReview();

        $webhookService = \Mockery::mock(N8nWebhookService::class);
        $webhookService->shouldReceive('toActionUrl')->never();
        $webhookService->shouldReceive('sendByTemplate')->never();

        $this->app->instance(N8nWebhookService::class, $webhookService);
        $kpiService = \Mockery::mock(KpiCalculationService::class);
        $kpiService->shouldReceive('recalculateForGuru')->twice();
        $this->app->instance(KpiCalculationService::class, $kpiService);

        $guruRequest = Request::create('/programs/' . $payload['program']->id . '/guru/' . $payload['pendingGuruId'] . '/status', 'POST', [
            'program_status_id' => $payload['tidakHadirStatusId'],
            'absence_reason' => 'Tidak sihat',
        ]);
        $guruRequest->setUserResolver(fn (): User => $payload['pendingUser']);

        $guruResponse = app(\App\Http\Controllers\ProgramParticipationController::class)->updateStatus(
            $guruRequest,
            $payload['program'],
            $payload['pendingGuruId']
        );

        $this->assertSame(302, $guruResponse->getStatusCode());

        $reviewWebhookService = \Mockery::mock(N8nWebhookService::class);
        $reviewWebhookService->shouldReceive('toActionUrl')
            ->once()
            ->with(\Mockery::on(fn ($url) => is_string($url) && str_contains($url, '/programs/' . $payload['program']->id)))
            ->andReturn('https://example.test/programs/' . $payload['program']->id);
        $reviewWebhookService->shouldReceive('sendByTemplate')
            ->once()
            ->with(
                N8nWebhookService::KEY_TEXT_ALL_GURU_COMPLETED_THANKS,
                ['perkara' => 'status program'],
                'https://example.test/programs/' . $payload['program']->id
            );
        $this->app->instance(N8nWebhookService::class, $reviewWebhookService);
        $this->app->forgetInstance(\App\Http\Controllers\ProgramParticipationController::class);

        $adminRequest = Request::create('/programs/' . $payload['program']->id . '/teachers/' . $payload['pendingGuruId'] . '/absence-review', 'POST', [
            'decision' => 'approved',
        ]);
        $adminRequest->setUserResolver(fn (): User => $payload['adminUser']);

        $adminResponse = app(\App\Http\Controllers\ProgramParticipationController::class)->reviewAbsenceReason(
            $adminRequest,
            $payload['program'],
            $payload['pendingGuruId']
        );

        $this->assertSame(302, $adminResponse->getStatusCode());
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

    private function seedProgramWithPendingGurus(): Program
    {
        $kawasan = Kawasan::query()->create(['name' => 'Kawasan Sik']);
        $pasti = Pasti::query()->create([
            'kawasan_id' => $kawasan->id,
            'name' => 'PASTI Alpha',
        ]);

        $program = Program::query()->create([
            'pasti_id' => $pasti->id,
            'title' => 'Program Ujian',
            'program_date' => now()->addDay()->toDateString(),
            'program_time' => null,
            'location' => 'Dewan',
            'description' => null,
            'require_absence_reason' => false,
            'markah' => 3,
            'created_by' => 1,
        ]);

        foreach (['Test', 'Ahmad', 'Siti', 'Nurul'] as $name) {
            $user = User::query()->create([
                'name' => $name,
                'nama_samaran' => $name,
                'email' => strtolower($name).uniqid().'@example.test',
            ]);

            $guru = Guru::query()->create([
                'user_id' => $user->id,
                'pasti_id' => $pasti->id,
                'name' => $name,
                'email' => $user->email,
                'is_assistant' => false,
                'active' => true,
            ]);

            \DB::table('program_teacher')->insert([
                'program_id' => $program->id,
                'guru_id' => $guru->id,
                'program_status_id' => null,
                'absence_reason' => null,
                'absence_reason_status' => null,
                'absence_reason_reviewed_by' => null,
                'absence_reason_reviewed_at' => null,
                'updated_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $program;
    }

    private function seedProgramWithCompletedGurusExceptTest(): array
    {
        $kawasan = Kawasan::query()->create(['name' => 'Kawasan Sik']);
        $pasti = Pasti::query()->create([
            'kawasan_id' => $kawasan->id,
            'name' => 'PASTI Alpha',
        ]);

        $program = Program::query()->create([
            'pasti_id' => $pasti->id,
            'title' => 'Program Ujian',
            'program_date' => now()->addDay()->toDateString(),
            'program_time' => null,
            'location' => 'Dewan',
            'description' => null,
            'require_absence_reason' => false,
            'markah' => 3,
            'created_by' => 1,
        ]);

        $realGuruIds = [];
        $hadirStatusId = ProgramStatus::query()->where('code', 'HADIR')->value('id');

        foreach (['Test', 'Ahmad', 'Siti', 'Nurul'] as $index => $name) {
            $user = User::query()->create([
                'name' => $name,
                'nama_samaran' => $name,
                'email' => strtolower($name).uniqid().'@example.test',
            ]);
            $this->attachRole($user, 'guru');

            $guru = Guru::query()->create([
                'user_id' => $user->id,
                'pasti_id' => $pasti->id,
                'name' => $name,
                'email' => $user->email,
                'is_assistant' => false,
                'active' => true,
            ]);

            \DB::table('program_teacher')->insert([
                'program_id' => $program->id,
                'guru_id' => $guru->id,
                'program_status_id' => $index < 3 ? $hadirStatusId : null,
                'absence_reason' => null,
                'absence_reason_status' => null,
                'absence_reason_reviewed_by' => null,
                'absence_reason_reviewed_at' => null,
                'updated_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($name !== 'Test') {
                $realGuruIds[$name] = $guru->id;
            }
        }

        $pendingUser = User::query()->where('name', 'Nurul')->firstOrFail();

        return [
            'program' => $program,
            'user' => $pendingUser,
            'guruId' => $realGuruIds['Nurul'],
            'hadirStatusId' => ProgramStatus::query()->where('code', 'HADIR')->value('id'),
        ];
    }

    private function seedProgramAwaitingAbsenceReview(): array
    {
        $kawasan = Kawasan::query()->create(['name' => 'Kawasan Sik']);
        $pasti = Pasti::query()->create([
            'kawasan_id' => $kawasan->id,
            'name' => 'PASTI Alpha',
        ]);

        $program = Program::query()->create([
            'pasti_id' => $pasti->id,
            'title' => 'Program Ujian',
            'program_date' => now()->addDay()->toDateString(),
            'program_time' => null,
            'location' => 'Dewan',
            'description' => null,
            'require_absence_reason' => true,
            'markah' => 3,
            'created_by' => 1,
        ]);

        $hadirStatusId = (int) ProgramStatus::query()->where('code', 'HADIR')->value('id');
        $tidakHadirStatusId = (int) ProgramStatus::query()->where('code', 'TIDAK_HADIR')->value('id');

        $pendingUser = null;
        $pendingGuruId = null;

        foreach (['Test', 'Ahmad', 'Nurul'] as $index => $name) {
            $user = User::query()->create([
                'name' => $name,
                'nama_samaran' => $name,
                'email' => strtolower($name).uniqid().'@example.test',
            ]);
            $this->attachRole($user, 'guru');

            $guru = Guru::query()->create([
                'user_id' => $user->id,
                'pasti_id' => $pasti->id,
                'name' => $name,
                'email' => $user->email,
                'is_assistant' => false,
                'active' => true,
            ]);

            \DB::table('program_teacher')->insert([
                'program_id' => $program->id,
                'guru_id' => $guru->id,
                'program_status_id' => $index === 1 ? $hadirStatusId : null,
                'absence_reason' => null,
                'absence_reason_status' => null,
                'absence_reason_reviewed_by' => null,
                'absence_reason_reviewed_at' => null,
                'updated_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($name === 'Nurul') {
                $pendingUser = $user;
                $pendingGuruId = $guru->id;
            }
        }

        return [
            'program' => $program,
            'pendingUser' => $pendingUser,
            'pendingGuruId' => $pendingGuruId,
            'adminUser' => $this->masterAdmin(),
            'tidakHadirStatusId' => $tidakHadirStatusId,
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
