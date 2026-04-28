<?php

namespace Tests\Feature;

use App\Http\Controllers\ProgramController;
use App\Models\Guru;
use App\Models\Kawasan;
use App\Models\Pasti;
use App\Models\Program;
use App\Models\User;
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

        \DB::table('roles')->insert([
            ['name' => 'master_admin', 'guard_name' => 'web'],
            ['name' => 'admin', 'guard_name' => 'web'],
            ['name' => 'guru', 'guard_name' => 'web'],
        ]);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('program_teacher');
        Schema::dropIfExists('programs');
        Schema::dropIfExists('gurus');
        Schema::dropIfExists('pastis');
        Schema::dropIfExists('kawasans');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('roles');
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

    public function test_send_thanks_sends_thank_you_message_when_test_is_ignored(): void
    {
        $program = $this->seedProgramWithCompletedGurusExceptTest();

        $webhookService = \Mockery::mock(N8nWebhookService::class);
        $webhookService->shouldReceive('toActionUrl')
            ->once()
            ->with(\Mockery::on(fn ($url) => is_string($url) && str_contains($url, '/programs/' . $program->id)))
            ->andReturn('https://example.test/programs/' . $program->id);
        $webhookService->shouldReceive('sendByTemplate')
            ->once()
            ->with(
                N8nWebhookService::KEY_TEXT_ALL_GURU_COMPLETED_THANKS,
                \Mockery::on(fn (array $variables) => ($variables['perkara'] ?? null) === 'status program' && filled($variables['tarikh'] ?? null)),
                'https://example.test/programs/' . $program->id
            );

        $this->app->instance(N8nWebhookService::class, $webhookService);

        $request = Request::create('/programs/' . $program->id . '/send-thanks', 'POST');
        $request->setUserResolver(fn (): User => $this->masterAdmin());

        $response = app(ProgramController::class)->sendThanks($request, $program);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('Mesej telah berjaya dihantar ke group guru.', $response->getSession()->get('status'));
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

    private function seedProgramWithCompletedGurusExceptTest(): Program
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

        foreach (['Test', 'Ahmad', 'Siti', 'Nurul'] as $index => $name) {
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
                'program_status_id' => $index === 0 ? null : 1,
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
}
