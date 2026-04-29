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

        Schema::create('admin_messages', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('sender_id')->nullable();
            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->string('image_path')->nullable();
            $table->boolean('sent_to_all')->default(false);
            $table->unsignedBigInteger('deleted_by_id')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('admin_message_recipients', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('admin_message_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
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

        Schema::create('pasti_information_requests', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('pasti_id');
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('guru_salary_requests', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('guru_id');
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->decimal('gaji', 10, 2)->nullable();
            $table->decimal('elaun', 10, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('leave_notices', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('guru_id');
            $table->date('leave_date');
            $table->date('leave_until')->nullable();
            $table->timestamps();
        });

        Schema::create('claims', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('pasti_id')->nullable();
            $table->string('status')->default('pending');
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
        Schema::dropIfExists('claims');
        Schema::dropIfExists('leave_notices');
        Schema::dropIfExists('guru_salary_requests');
        Schema::dropIfExists('pasti_information_requests');
        Schema::dropIfExists('program_teacher');
        Schema::dropIfExists('programs');
        Schema::dropIfExists('program_statuses');
        Schema::dropIfExists('gurus');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('admin_message_recipients');
        Schema::dropIfExists('admin_messages');
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

    public function test_program_show_page_can_be_rendered(): void
    {
        Notification::fake();

        [$program, , , $admin] = $this->createProgramFixtures();

        $response = $this->actingAs($admin)
            ->get(route('programs.show', $program));

        $response
            ->assertOk()
            ->assertSee('Program Ujian')
            ->assertSee('Menunggu Semakan')
            ->assertSee('Complete')
            ->assertSee('Tiada semakan yang menunggu pada masa ini.')
            ->assertSee('Tiada rekod complete untuk dipaparkan.')
            ->assertDontSee('<table class="table-base">', false);
    }

    public function test_program_show_page_displays_avatar_and_latest_updated_participation_first(): void
    {
        Notification::fake();

        [$program, $guruUser, $absentStatus, $admin, $latestGuruUser] = $this->createProgramFixtures(withSecondGuru: true);

        \DB::table('program_teacher')
            ->where('program_id', $program->id)
            ->where('guru_id', $guruUser->guru->id)
            ->update([
                'program_status_id' => $absentStatus->id,
                'absence_reason' => 'Ada urusan keluarga.',
                'absence_reason_status' => 'pending',
            ]);

        \DB::table('program_teacher')
            ->where('program_id', $program->id)
            ->where('guru_id', $latestGuruUser->guru->id)
            ->update([
                'program_status_id' => $absentStatus->id,
                'absence_reason' => 'Sakit.',
                'absence_reason_status' => 'approved',
            ]);

        $response = $this->actingAs($admin)
            ->get(route('programs.show', $program));

        $response
            ->assertOk()
            ->assertSee('Menunggu Semakan')
            ->assertSee('Complete')
            ->assertSee('data-testid="program-participation-avatar"', false)
            ->assertViewHas('adminPendingReviewParticipations', function ($participations): bool {
                return $participations->count() === 1
                    && $participations->first()->guru->display_name === 'Cikgu Program';
            })
            ->assertViewHas('adminCompletedParticipations', function ($participations): bool {
                return $participations->count() === 1
                    && $participations->first()->guru->display_name === 'Cikgu Kedua';
            });
    }

    public function test_program_show_page_defaults_to_submitted_gurus_and_exposes_toggle_for_all_gurus(): void
    {
        Notification::fake();

        [$program, , , $admin, $latestGuruUser] = $this->createProgramFixtures(
            withSecondGuru: true,
            secondGuruHasStatus: false
        );

        $response = $this->actingAs($admin)
            ->get(route('programs.show', $program));

        $response
            ->assertOk()
            ->assertSee('Semua guru')
            ->assertSee('showAllTeachers: false', false)
            ->assertViewHas('submittedParticipations', function ($participations): bool {
                return $participations->count() === 1
                    && $participations->first()->guru->display_name === 'Cikgu Program';
            })
            ->assertViewHas('allParticipations', function ($participations) use ($latestGuruUser): bool {
                return $participations->count() === 2
                    && $participations->first()->guru->display_name === $latestGuruUser->display_name;
            });
    }

    public function test_program_show_page_displays_response_box_for_guru_with_pending_status(): void
    {
        Notification::fake();

        [$program, , , , $latestGuruUser] = $this->createProgramFixtures(
            withSecondGuru: true,
            secondGuruHasStatus: false
        );

        $response = $this->actingAs($latestGuruUser)
            ->get(route('programs.show', $program));

        $response
            ->assertOk()
            ->assertSee('Respon Program')
            ->assertSee('Perlu Respon')
            ->assertSee('name="program_status_id"', false)
            ->assertSee(route('programs.teachers.status.update', [$program, $latestGuruUser->guru->id]), false);
    }

    public function test_program_show_page_for_guru_uses_avatar_only_status_groups(): void
    {
        Notification::fake();

        [$program, , $absentStatus, , $latestGuruUser] = $this->createProgramFixtures(
            withSecondGuru: true,
            secondGuruHasStatus: false
        );

        $thirdUser = User::query()->create([
            'name' => 'Cikgu Tambahan',
            'nama_samaran' => 'Cikgu Tambahan',
            'email' => 'guru-tambahan@example.test',
            'avatar_path' => 'avatars/cikgu-tambahan.jpg',
        ]);
        $this->attachRole($thirdUser, 'guru');

        $thirdGuru = Guru::query()->create([
            'user_id' => $thirdUser->id,
            'pasti_id' => $latestGuruUser->guru->pasti_id,
            'active' => true,
        ]);
        $thirdUser->setRelation('guru', $thirdGuru);

        \DB::table('program_teacher')->insert([
            'program_id' => $program->id,
            'guru_id' => $thirdGuru->id,
            'program_status_id' => $absentStatus->id,
            'updated_by' => $latestGuruUser->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($latestGuruUser)
            ->get(route('programs.show', $program));

        $response
            ->assertOk()
            ->assertSee('Paparan ringkas ikut status')
            ->assertSee('data-testid="program-guru-group-hadir"', false)
            ->assertSee('data-testid="program-guru-group-tidak_hadir"', false)
            ->assertSee('data-testid="program-guru-group-menunggu"', false)
            ->assertDontSee('data-testid="program-participation-card"', false);
    }

    public function test_program_show_page_for_admin_uses_pending_review_and_complete_tabs(): void
    {
        Notification::fake();

        [$program, $guruUser, $absentStatus, $admin, $latestGuruUser] = $this->createProgramFixtures(
            withSecondGuru: true,
            secondGuruHasStatus: false
        );

        \DB::table('program_teacher')
            ->where('program_id', $program->id)
            ->where('guru_id', $guruUser->guru->id)
            ->update([
                'program_status_id' => $absentStatus->id,
                'absence_reason' => 'Ada urusan keluarga.',
                'absence_reason_status' => 'pending',
            ]);

        \DB::table('program_teacher')
            ->where('program_id', $program->id)
            ->where('guru_id', $latestGuruUser->guru->id)
            ->update([
                'program_status_id' => $absentStatus->id,
                'absence_reason' => 'Sakit.',
                'absence_reason_status' => 'approved',
            ]);

        $response = $this->actingAs($admin)
            ->get(route('programs.show', $program));

        $response
            ->assertOk()
            ->assertSee('Menunggu Semakan')
            ->assertSee('Complete')
            ->assertSee('data-testid="program-participation-card"', false)
            ->assertViewHas('adminPendingReviewParticipations', function ($participations): bool {
                return $participations->count() === 1
                    && $participations->first()->guru->display_name === 'Cikgu Program';
            })
            ->assertViewHas('adminCompletedParticipations', function ($participations): bool {
                return $participations->count() === 1
                    && $participations->first()->guru->display_name === 'Cikgu Kedua';
            });
    }

    public function test_program_menu_badge_uses_pending_absence_reason_approval_count(): void
    {
        Notification::fake();

        [$program, $guruUser, $absentStatus, $admin, $latestGuruUser] = $this->createProgramFixtures(withSecondGuru: true);

        \DB::table('program_teacher')
            ->where('program_id', $program->id)
            ->where('guru_id', $guruUser->guru->id)
            ->update([
                'program_status_id' => $absentStatus->id,
                'absence_reason' => 'Anak kurang sihat.',
                'absence_reason_status' => 'pending',
            ]);

        \DB::table('program_teacher')
            ->where('program_id', $program->id)
            ->where('guru_id', $latestGuruUser->guru->id)
            ->update([
                'absence_reason' => 'Ada urusan keluarga.',
                'absence_reason_status' => 'pending',
            ]);

        $response = $this->actingAs($admin)
            ->get(route('programs.show', $program));

        $response
            ->assertOk()
            ->assertSee('data-testid="menu-program-badge"', false)
            ->assertSee('>2</span>', false);
    }

    public function test_program_index_card_displays_pending_absence_reason_approval_badge(): void
    {
        Notification::fake();

        [$program, $guruUser, $absentStatus, $admin, $latestGuruUser] = $this->createProgramFixtures(withSecondGuru: true);

        \DB::table('program_teacher')
            ->where('program_id', $program->id)
            ->where('guru_id', $guruUser->guru->id)
            ->update([
                'program_status_id' => $absentStatus->id,
                'absence_reason' => 'Anak kurang sihat.',
                'absence_reason_status' => 'pending',
            ]);

        \DB::table('program_teacher')
            ->where('program_id', $program->id)
            ->where('guru_id', $latestGuruUser->guru->id)
            ->update([
                'absence_reason' => 'Ada urusan keluarga.',
                'absence_reason_status' => 'pending',
            ]);

        $response = $this->actingAs($admin)
            ->get(route('programs.index'));

        $response
            ->assertOk()
            ->assertSee('data-testid="program-card-pending-badge"', false)
            ->assertSee('>2</span>', false);
    }

    public function test_admin_status_update_shows_sweetalert_and_disables_target_form(): void
    {
        Notification::fake();

        [$program, $guruUser, $absentStatus, $admin] = $this->createProgramFixtures();

        \DB::table('program_teacher')
            ->where('program_id', $program->id)
            ->where('guru_id', $guruUser->guru->id)
            ->update([
                'program_status_id' => null,
                'absence_reason' => null,
                'absence_reason_status' => null,
            ]);

        $response = $this->actingAs($admin)
            ->from(route('programs.show', $program))
            ->followingRedirects()
            ->post(route('programs.teachers.status.update', [$program, $guruUser->guru->id]), [
                'program_status_id' => $absentStatus->id,
                'absence_reason' => 'Tidak sihat.',
            ]);

        $response
            ->assertOk()
            ->assertSee('data-testid="program-status-success-alert"', false)
            ->assertSee('Dah berjaya update')
            ->assertSee('data-testid="program-admin-status-form-disabled"', false)
            ->assertSee('name="program_status_id"', false)
            ->assertSee('name="absence_reason"', false)
            ->assertSee('disabled', false);
    }

    public function test_admin_absence_review_shows_sweetalert_and_disables_review_buttons(): void
    {
        Notification::fake();

        [$program, $guruUser, $absentStatus, $admin] = $this->createProgramFixtures();

        $this->actingAs($guruUser)
            ->post(route('programs.teachers.status.update', [$program, $guruUser->guru->id]), [
                'program_status_id' => $absentStatus->id,
                'absence_reason' => 'Anak sakit.',
            ]);

        $response = $this->actingAs($admin)
            ->from(route('programs.show', $program))
            ->followingRedirects()
            ->post(route('programs.teachers.absence-review', [$program, $guruUser->guru->id]), [
                'decision' => 'approved',
            ]);

        $response
            ->assertOk()
            ->assertSee('data-testid="program-status-success-alert"', false)
            ->assertSee('Dah berjaya update')
            ->assertSee('data-testid="program-admin-review-buttons-disabled"', false)
            ->assertSee('Luluskan alasan')
            ->assertSee('Tolak alasan')
            ->assertSee('disabled', false);
    }

    public function test_admin_absence_review_buttons_remain_disabled_after_page_refresh(): void
    {
        Notification::fake();

        [$program, $guruUser, $absentStatus, $admin] = $this->createProgramFixtures();

        $this->actingAs($guruUser)
            ->post(route('programs.teachers.status.update', [$program, $guruUser->guru->id]), [
                'program_status_id' => $absentStatus->id,
                'absence_reason' => 'Anak sakit.',
            ]);

        $this->actingAs($admin)
            ->post(route('programs.teachers.absence-review', [$program, $guruUser->guru->id]), [
                'decision' => 'approved',
            ]);

        $this->actingAs($admin)
            ->get(route('programs.show', $program));

        $response = $this->actingAs($admin)
            ->get(route('programs.show', $program));

        $response
            ->assertOk()
            ->assertSee('data-testid="program-admin-review-buttons-disabled"', false)
            ->assertSee('Luluskan alasan')
            ->assertSee('Tolak alasan')
            ->assertSee('disabled', false)
            ->assertDontSee('data-testid="program-status-success-alert"', false);
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
     * @return array{0: Program, 1: User, 2: ProgramStatus, 3: User, 4: ?User}
     */
    private function createProgramFixtures(bool $withSecondGuru = false, bool $secondGuruHasStatus = true): array
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
            'avatar_path' => 'avatars/cikgu-program.jpg',
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
            'created_at' => now()->subMinutes(10),
            'updated_at' => now()->subMinutes(10),
        ]);

        $latestGuruUser = null;

        if ($withSecondGuru) {
            $latestGuruUser = User::query()->create([
                'name' => 'Cikgu Kedua',
                'nama_samaran' => 'Cikgu Kedua',
                'email' => 'guru-kedua@example.test',
                'avatar_path' => 'avatars/cikgu-kedua.jpg',
            ]);
            $this->attachRole($latestGuruUser, 'guru');

            $latestGuru = Guru::query()->create([
                'user_id' => $latestGuruUser->id,
                'pasti_id' => $pasti->id,
                'active' => true,
            ]);
            $latestGuruUser->setRelation('guru', $latestGuru);

            \DB::table('program_teacher')->insert([
                'program_id' => $program->id,
                'guru_id' => $latestGuru->id,
                'program_status_id' => $secondGuruHasStatus ? $absentStatus->id : null,
                'updated_by' => $admin->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return [$program, $guruUser, $absentStatus, $admin, $latestGuruUser];
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
