<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureGuruWebOnboardingCompleted;
use App\Livewire\ProgramIndex;
use App\Models\Guru;
use App\Models\Program;
use App\Models\ProgramStatus;
use App\Models\User;
use App\Services\KpiCalculationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminGuruProgramAccessTest extends TestCase
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
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('program_statuses', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->boolean('is_hadir')->default(false);
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

        ProgramStatus::query()->insert([
            ['name' => 'Hadir', 'code' => 'HADIR', 'is_hadir' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Tidak Hadir', 'code' => 'TIDAK_HADIR', 'is_hadir' => false, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->withoutMiddleware(EnsureGuruWebOnboardingCompleted::class);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('program_teacher');
        Schema::dropIfExists('programs');
        Schema::dropIfExists('program_statuses');
        Schema::dropIfExists('gurus');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_admin_switched_to_guru_mode_can_see_assigned_programs_matched_by_email(): void
    {
        [$admin, $guru] = $this->seedAdminWithGuruProfileByEmailOnly('program-switch@example.test');

        $ownProgram = Program::query()->create([
            'title' => 'Program Saya',
            'program_date' => now()->addDay()->toDateString(),
            'location' => 'Dewan A',
            'created_by' => $admin->id,
        ]);

        $otherProgram = Program::query()->create([
            'title' => 'Program Orang Lain',
            'program_date' => now()->addDays(2)->toDateString(),
            'location' => 'Dewan B',
            'created_by' => $admin->id,
        ]);

        \DB::table('program_teacher')->insert([
            [
                'program_id' => $ownProgram->id,
                'guru_id' => $guru->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'program_id' => $otherProgram->id,
                'guru_id' => Guru::query()->create([
                    'name' => 'Guru Lain',
                    'email' => 'guru-lain@example.test',
                    'active' => true,
                ])->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->actingAs($admin)
            ->withSession(['login_using_admin_role' => true])
            ->post(route('impersonation.switch-to-guru-mode'))
            ->assertRedirect(route('dashboard'));

        $admin->refresh();

        $this->assertTrue($admin->hasRole('guru'));
        $this->assertSame($admin->id, $guru->fresh()->user_id);

        $session = app('session')->driver();
        $session->start();
        $session->put([
            'login_using_admin_role' => true,
            'active_role_mode' => 'guru',
        ]);

        $request = Request::create('/programs', 'GET');
        $request->setLaravelSession($session);
        app()->instance('request', $request);
        auth()->setUser($admin);

        $response = app(ProgramIndex::class)->render();
        $programs = $response->getData()['programs'];

        $this->assertInstanceOf(LengthAwarePaginator::class, $programs);
        $this->assertSame(['Program Saya'], $programs->pluck('title')->all());
    }

    public function test_admin_switched_to_guru_mode_can_update_own_program_attendance_matched_by_email(): void
    {
        [$admin, $guru] = $this->seedAdminWithGuruProfileByEmailOnly('program-update@example.test');

        $program = Program::query()->create([
            'title' => 'Program Kehadiran',
            'program_date' => now()->addDay()->toDateString(),
            'location' => 'Dewan',
            'created_by' => $admin->id,
        ]);

        \DB::table('program_teacher')->insert([
            'program_id' => $program->id,
            'guru_id' => $guru->id,
            'program_status_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->withSession(['login_using_admin_role' => true])
            ->post(route('impersonation.switch-to-guru-mode'))
            ->assertRedirect(route('dashboard'));

        $admin->refresh();
        $hadirStatusId = (int) ProgramStatus::query()->where('code', 'HADIR')->value('id');
        $kpiService = \Mockery::mock(KpiCalculationService::class);
        $kpiService->shouldReceive('recalculateForGuru')->once();
        $this->app->instance(KpiCalculationService::class, $kpiService);

        $this->actingAs($admin)
            ->withSession([
                'login_using_admin_role' => true,
                'active_role_mode' => 'guru',
            ])
            ->post(route('programs.teachers.status.update', [$program, $guru->id]), [
                'program_status_id' => $hadirStatusId,
            ])
            ->assertStatus(302);

        $this->assertDatabaseHas('program_teacher', [
            'program_id' => $program->id,
            'guru_id' => $guru->id,
            'program_status_id' => $hadirStatusId,
            'updated_by' => $admin->id,
        ]);
    }

    /**
     * @return array{0: User, 1: Guru}
     */
    private function seedAdminWithGuruProfileByEmailOnly(string $email): array
    {
        $admin = User::query()->create([
            'name' => 'Admin Program',
            'nama_samaran' => 'Admin Program',
            'email' => $email,
        ]);
        $this->attachRole($admin, 'admin');

        $guru = Guru::query()->create([
            'name' => 'Admin Program',
            'email' => $email,
            'active' => true,
        ]);

        return [$admin, $guru];
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
