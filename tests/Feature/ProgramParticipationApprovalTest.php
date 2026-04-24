<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureGuruWebOnboardingCompleted;
use App\Models\Guru;
use App\Models\Kawasan;
use App\Models\Pasti;
use App\Models\Program;
use App\Models\ProgramStatus;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProgramParticipationApprovalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('nama_samaran')->nullable();
            $table->string('email')->unique();
            $table->string('avatar_path')->nullable();
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

        Schema::create('kawasans', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('pastis', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('kawasan_id');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('admin_pasti', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('pasti_id');
            $table->timestamps();
        });

        Schema::create('gurus', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->unsignedBigInteger('pasti_id')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_assistant')->default(false);
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

        Schema::create('kpi_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('guru_id')->unique();
            $table->unsignedInteger('total_invited')->default(0);
            $table->unsignedInteger('total_hadir')->default(0);
            $table->decimal('score', 5, 2)->default(0);
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();
        });

        \DB::table('roles')->insert([
            ['name' => 'master_admin', 'guard_name' => 'web'],
            ['name' => 'admin', 'guard_name' => 'web'],
            ['name' => 'guru', 'guard_name' => 'web'],
        ]);

        $this->withoutMiddleware(EnsureGuruWebOnboardingCompleted::class);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('kpi_snapshots');
        Schema::dropIfExists('program_teacher');
        Schema::dropIfExists('programs');
        Schema::dropIfExists('program_statuses');
        Schema::dropIfExists('gurus');
        Schema::dropIfExists('admin_pasti');
        Schema::dropIfExists('pastis');
        Schema::dropIfExists('kawasans');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_guru_must_provide_absence_reason_when_program_requires_it(): void
    {
        Notification::fake();

        [$program, $guruUser, $absentStatus] = $this->createProgramFixtures();

        $response = $this->actingAs($guruUser)
            ->from(route('programs.show', $program))
            ->post(route('programs.teachers.status.update', [$program, $guruUser->guru->id]), [
                'program_status_id' => $absentStatus->id,
                'absence_reason' => '',
            ]);

        $response->assertRedirect(route('programs.show', $program));
        $response->assertSessionHasErrors('absence_reason');
    }

    public function test_absence_reason_submission_stays_pending_and_does_not_add_kpi_before_admin_review(): void
    {
        Notification::fake();

        [$program, $guruUser, $absentStatus] = $this->createProgramFixtures();

        $response = $this->actingAs($guruUser)
            ->post(route('programs.teachers.status.update', [$program, $guruUser->guru->id]), [
                'program_status_id' => $absentStatus->id,
                'absence_reason' => 'Demam dan ada sijil cuti sakit.',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('program_teacher', [
            'program_id' => $program->id,
            'guru_id' => $guruUser->guru->id,
            'absence_reason' => 'Demam dan ada sijil cuti sakit.',
            'absence_reason_status' => 'pending',
        ]);

        $this->assertDatabaseHas('kpi_snapshots', [
            'guru_id' => $guruUser->guru->id,
            'score' => 0,
        ]);
    }

    public function test_admin_approval_awards_program_markah_to_guru_kpi(): void
    {
        Notification::fake();

        [$program, $guruUser, $absentStatus, $admin] = $this->createProgramFixtures();

        $this->actingAs($guruUser)
            ->post(route('programs.teachers.status.update', [$program, $guruUser->guru->id]), [
                'program_status_id' => $absentStatus->id,
                'absence_reason' => 'Menghadiri urusan keluarga.',
            ]);

        $response = $this->actingAs($admin)
            ->post(route('programs.teachers.absence-review', [$program, $guruUser->guru->id]), [
                'decision' => 'approved',
            ]);

        $response->assertRedirect(route('programs.show', $program));

        $this->assertDatabaseHas('program_teacher', [
            'program_id' => $program->id,
            'guru_id' => $guruUser->guru->id,
            'absence_reason_status' => 'approved',
        ]);

        $this->assertDatabaseHas('kpi_snapshots', [
            'guru_id' => $guruUser->guru->id,
            'score' => 4,
        ]);
    }

    public function test_admin_rejection_keeps_program_kpi_at_zero(): void
    {
        Notification::fake();

        [$program, $guruUser, $absentStatus, $admin] = $this->createProgramFixtures();

        $this->actingAs($guruUser)
            ->post(route('programs.teachers.status.update', [$program, $guruUser->guru->id]), [
                'program_status_id' => $absentStatus->id,
                'absence_reason' => 'Anak sakit.',
            ]);

        $response = $this->actingAs($admin)
            ->post(route('programs.teachers.absence-review', [$program, $guruUser->guru->id]), [
                'decision' => 'rejected',
            ]);

        $response->assertRedirect(route('programs.show', $program));

        $this->assertDatabaseHas('program_teacher', [
            'program_id' => $program->id,
            'guru_id' => $guruUser->guru->id,
            'absence_reason_status' => 'rejected',
        ]);

        $this->assertDatabaseHas('kpi_snapshots', [
            'guru_id' => $guruUser->guru->id,
            'score' => 0,
        ]);
    }

    /**
     * @return array{0: Program, 1: User, 2: ProgramStatus, 3: User}
     */
    private function createProgramFixtures(): array
    {
        $kawasan = Kawasan::query()->create(['name' => 'Kawasan Sik']);
        $pasti = Pasti::query()->create([
            'kawasan_id' => $kawasan->id,
            'name' => 'PASTI Al Hikmah',
        ]);

        $admin = User::query()->create([
            'name' => 'Admin Program',
            'nama_samaran' => 'Admin Program',
            'email' => 'admin-program@example.test',
        ]);
        $this->attachRole($admin, 'admin');
        $admin->assignedPastis()->sync([$pasti->id]);

        $guruUser = User::query()->create([
            'name' => 'Cikgu Program',
            'nama_samaran' => 'Cikgu Program',
            'email' => 'guru-program@example.test',
        ]);
        $this->attachRole($guruUser, 'guru');

        $guru = Guru::query()->create([
            'user_id' => $guruUser->id,
            'pasti_id' => $pasti->id,
            'active' => true,
        ]);
        $guruUser->setRelation('guru', $guru);

        $hadirStatus = ProgramStatus::query()->create([
            'name' => 'Hadir',
            'code' => 'HADIR',
            'is_hadir' => true,
        ]);

        $absentStatus = ProgramStatus::query()->create([
            'name' => 'Tidak Hadir',
            'code' => 'TIDAK_HADIR',
            'is_hadir' => false,
        ]);

        $program = Program::query()->create([
            'pasti_id' => $pasti->id,
            'title' => 'Program Ujian',
            'program_date' => now()->toDateString(),
            'require_absence_reason' => true,
            'markah' => 4,
            'created_by' => $admin->id,
        ]);

        \DB::table('program_teacher')->insert([
            'program_id' => $program->id,
            'guru_id' => $guru->id,
            'program_status_id' => $hadirStatus->id,
            'updated_by' => $admin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$program, $guruUser, $absentStatus, $admin];
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
