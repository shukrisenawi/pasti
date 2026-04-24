<?php

namespace Tests\Feature;

use App\Http\Controllers\DashboardController;
use App\Http\Middleware\EnsureGuruWebOnboardingCompleted;
use App\Models\Guru;
use App\Models\Pasti;
use App\Models\Program;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Tests\TestCase;

class DashboardGuruProgramsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('nama_samaran')->nullable();
            $table->date('tarikh_lahir')->nullable();
            $table->date('tarikh_exp_skim_pas')->nullable();
            $table->string('email')->unique();
            $table->string('avatar_path')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->boolean('force_password_change')->default(false);
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

        Schema::create('pastis', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('kawasan_id')->nullable();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('gurus', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->unsignedBigInteger('pasti_id')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('marital_status')->nullable();
            $table->string('kursus_guru')->nullable();
            $table->date('joined_at')->nullable();
            $table->boolean('terima_anugerah')->default(false);
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

        Schema::create('admin_message_replies', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('admin_message_id');
            $table->unsignedBigInteger('sender_id')->nullable();
            $table->text('body')->nullable();
            $table->string('image_path')->nullable();
            $table->unsignedBigInteger('deleted_by_id')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('announcements', function (Blueprint $table): void {
            $table->id();
            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->date('expires_at')->nullable();
            $table->unsignedBigInteger('sent_by')->nullable();
            $table->timestamps();
        });

        Schema::create('announcement_user', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('announcement_id');
            $table->unsignedBigInteger('user_id');
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

        Schema::create('ajk_positions', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('user_ajk_positions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('ajk_position_id');
            $table->unsignedBigInteger('assigned_by')->nullable();
            $table->timestamps();
        });

        Schema::create('leave_notices', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('guru_id');
            $table->date('leave_date');
            $table->date('leave_until')->nullable();
            $table->timestamps();
        });

        \DB::table('roles')->insert([
            ['name' => 'guru', 'guard_name' => 'web'],
        ]);

        DB::connection()->getPdo()->sqliteCreateFunction('DATE_FORMAT', function (?string $value, string $format): ?string {
            if ($value === null) {
                return null;
            }

            return match ($format) {
                '%m-%d' => date('m-d', strtotime($value)),
                default => date($format, strtotime($value)),
            };
        }, 2);
        DB::connection()->getPdo()->sqliteCreateFunction('DATEDIFF', function (?string $left, ?string $right): int {
            if ($left === null || $right === null) {
                return 0;
            }

            return (int) floor((strtotime($left) - strtotime($right)) / 86400);
        }, 2);
        DB::connection()->getPdo()->sqliteCreateFunction('LEAST', fn (?string $left, ?string $right): ?string => match (true) {
            $left === null => $right,
            $right === null => $left,
            default => strcmp($left, $right) <= 0 ? $left : $right,
        }, 2);
        DB::connection()->getPdo()->sqliteCreateFunction('GREATEST', fn (?string $left, ?string $right): ?string => match (true) {
            $left === null => $right,
            $right === null => $left,
            default => strcmp($left, $right) >= 0 ? $left : $right,
        }, 2);

        $this->withoutMiddleware(EnsureGuruWebOnboardingCompleted::class);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('user_ajk_positions');
        Schema::dropIfExists('ajk_positions');
        Schema::dropIfExists('leave_notices');
        Schema::dropIfExists('guru_salary_requests');
        Schema::dropIfExists('pasti_information_requests');
        Schema::dropIfExists('announcement_user');
        Schema::dropIfExists('announcements');
        Schema::dropIfExists('admin_message_replies');
        Schema::dropIfExists('admin_message_recipients');
        Schema::dropIfExists('admin_messages');
        Schema::dropIfExists('program_teacher');
        Schema::dropIfExists('programs');
        Schema::dropIfExists('program_statuses');
        Schema::dropIfExists('gurus');
        Schema::dropIfExists('pastis');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_dashboard_guru_hides_programs_with_submitted_attendance_status(): void
    {
        $user = User::query()->create([
            'name' => 'Cikgu Dashboard',
            'nama_samaran' => 'Cikgu Dashboard',
            'email' => 'guru-dashboard@example.test',
        ]);
        $this->attachRole($user, 'guru');

        $pasti = Pasti::query()->create([
            'name' => 'PASTI Dashboard',
        ]);

        $guru = Guru::query()->create([
            'user_id' => $user->id,
            'pasti_id' => $pasti->id,
            'name' => $user->name,
            'active' => true,
        ]);

        $pendingProgram = Program::query()->create([
            'pasti_id' => $pasti->id,
            'title' => 'Program Belum Hantar Kehadiran',
            'program_date' => now()->addDay()->toDateString(),
            'created_by' => $user->id,
        ]);

        $submittedProgram = Program::query()->create([
            'pasti_id' => $pasti->id,
            'title' => 'Program Sudah Hantar Kehadiran',
            'program_date' => now()->addDays(2)->toDateString(),
            'created_by' => $user->id,
        ]);

        \DB::table('program_teacher')->insert([
            [
                'program_id' => $pendingProgram->id,
                'guru_id' => $guru->id,
                'program_status_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'program_id' => $submittedProgram->id,
                'guru_id' => $guru->id,
                'program_status_id' => 99,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $request = Request::create('/dashboard', 'GET');
        $request->setUserResolver(fn () => $user);

        $response = app(DashboardController::class)($request);

        $this->assertInstanceOf(View::class, $response);

        $latestPrograms = $response->getData()['latestPrograms'];

        $this->assertInstanceOf(Collection::class, $latestPrograms);
        $this->assertSame(
            ['Program Belum Hantar Kehadiran'],
            $latestPrograms->pluck('title')->all()
        );
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
