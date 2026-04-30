<?php

namespace Tests\Feature;

use App\Http\Controllers\PastiReportController;
use App\Models\Guru;
use App\Models\Pasti;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PastiReportTest extends TestCase
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

        Schema::create('pastis', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('kawasan_id')->nullable();
            $table->string('name');
            $table->string('address')->nullable();
            $table->timestamps();
        });

        Schema::create('admin_pasti', function (Blueprint $table): void {
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('pasti_id');
            $table->timestamps();
        });

        Schema::create('gurus', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('pasti_id')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('kad_pengenalan')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('is_assistant')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('guru_salary_requests', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('guru_id')->nullable();
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->decimal('gaji', 10, 2)->nullable();
            $table->decimal('elaun', 10, 2)->nullable();
            $table->decimal('elaun_transit', 10, 2)->nullable();
            $table->decimal('elaun_lain', 10, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('pasti_information_requests', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('pasti_id')->nullable();
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('jumlah_guru')->nullable();
            $table->unsignedInteger('jumlah_pembantu_guru')->nullable();
            $table->unsignedInteger('murid_lelaki_4_tahun')->nullable();
            $table->unsignedInteger('murid_perempuan_4_tahun')->nullable();
            $table->unsignedInteger('murid_lelaki_5_tahun')->nullable();
            $table->unsignedInteger('murid_perempuan_5_tahun')->nullable();
            $table->unsignedInteger('murid_lelaki_6_tahun')->nullable();
            $table->unsignedInteger('murid_perempuan_6_tahun')->nullable();
            $table->timestamps();
        });

        \DB::table('roles')->insert([
            ['name' => 'master_admin', 'guard_name' => 'web'],
            ['name' => 'admin', 'guard_name' => 'web'],
        ]);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('pasti_information_requests');
        Schema::dropIfExists('guru_salary_requests');
        Schema::dropIfExists('gurus');
        Schema::dropIfExists('admin_pasti');
        Schema::dropIfExists('pastis');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_pasti_report_orders_by_pasti_and_name_and_uses_latest_completed_salary(): void
    {
        $admin = $this->createAdmin('master_admin');
        $this->setAuthenticatedUser($admin);

        $pastiB = Pasti::query()->create(['name' => 'PASTI BETA', 'address' => 'ALAMAT BETA']);
        $pastiA = Pasti::query()->create(['name' => 'PASTI ALFA', 'address' => 'ALAMAT ALFA']);

        $zainab = $this->createGuru($pastiA, 'Zainab', false, '900101-01-1234', '0123000000');
        $amina = $this->createGuru($pastiA, 'Amina', true, '880202-02-2345', '0134000000');
        $badrul = $this->createGuru($pastiB, 'Badrul', true, '870303-03-3456', '0145000000');

        $this->insertSalary($zainab->id, now()->subDays(3), 1200, 150, 25);
        $this->insertSalary($zainab->id, now()->subDay(), 1400, 180, 35);
        $this->insertSalary($amina->id, now()->subDays(2), 1000, 120, 15);
        $this->insertSalary($badrul->id, now()->subDays(4), 900, 90, 10);

        $view = app(PastiReportController::class)->index();
        $reports = $view->getData()['reports'];
        $template = file_get_contents(resource_path('views/pasti-reports/index.blade.php'));

        $this->assertSame(
            ['Amina', 'Zainab', 'Badrul'],
            collect($reports->items())->pluck('name')->all()
        );
        $this->assertSame('180.00', $reports->items()[1]->latestCompletedSalaryRequest?->elaun);
        $this->assertSame('35.00', $reports->items()[1]->latestCompletedSalaryRequest?->elaun_lain);
        $this->assertFalse((bool) $reports->items()[1]->active);
        $this->assertIsString($template);
        $this->assertStringContainsString('Elaun</th>', $template);
        $this->assertStringContainsString('Elaun Transit', $template);
        $this->assertStringContainsString('Elaun Lain', $template);
        $this->assertStringNotContainsString('Elaun Tambahan', $template);
        $this->assertStringNotContainsString('Nama PASTI', $template);
    }

    public function test_pasti_report_includes_maklumat_pasti_tab_with_latest_completed_information(): void
    {
        $admin = $this->createAdmin('master_admin');
        $this->setAuthenticatedUser($admin);

        $pastiB = Pasti::query()->create(['name' => 'PASTI BETA']);
        $pastiA = Pasti::query()->create(['name' => 'PASTI ALFA']);

        $this->insertPastiInformation($pastiA->id, now()->subDays(3), 2, 1, 3, 4, 5, 6, 7, 8);
        $this->insertPastiInformation($pastiA->id, now()->subDay(), 4, 2, 10, 11, 12, 13, 14, 15);
        $this->insertPastiInformation($pastiB->id, now()->subDays(2), 3, 1, 1, 2, 3, 4, 5, 6);

        $view = app(PastiReportController::class)->index();
        $pastiReports = $view->getData()['pastiReports'];
        $template = file_get_contents(resource_path('views/pasti-reports/index.blade.php'));

        $this->assertSame(['PASTI ALFA', 'PASTI BETA'], collect($pastiReports->items())->pluck('name')->all());
        $this->assertSame(4, $pastiReports->items()[0]->latestCompletedInformationRequest?->jumlah_guru);
        $this->assertSame(2, $pastiReports->items()[0]->latestCompletedInformationRequest?->jumlah_pembantu_guru);
        $this->assertSame(75, $pastiReports->items()[0]->maklumat_pasti_jumlah);
        $this->assertIsString($template);
        $this->assertStringContainsString('Maklumat PASTI', $template);
        $this->assertStringContainsString('Elaun Guru', $template);
        $this->assertStringContainsString('4 Tahun (L)', $template);
        $this->assertStringContainsString('Jumlah', $template);
    }

    public function test_pasti_report_for_admin_is_limited_to_assigned_pasti(): void
    {
        $admin = $this->createAdmin('admin');
        $this->setAuthenticatedUser($admin);

        $visiblePasti = Pasti::query()->create(['name' => 'PASTI TAMAN', 'address' => 'JALAN SATU']);
        $hiddenPasti = Pasti::query()->create(['name' => 'PASTI LAMA', 'address' => 'JALAN DUA']);

        \DB::table('admin_pasti')->insert([
            'user_id' => $admin->id,
            'pasti_id' => $visiblePasti->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $visibleGuru = $this->createGuru($visiblePasti, 'Guru Nampak', true, '900101-01-1111', '0111111111');
        $hiddenGuru = $this->createGuru($hiddenPasti, 'Guru Sorok', true, '900101-01-2222', '0222222222');

        $this->insertSalary($visibleGuru->id, now()->subDay(), 1000, 100, 20);
        $this->insertSalary($hiddenGuru->id, now()->subDay(), 2000, 200, 30);

        $view = app(PastiReportController::class)->index();
        $reports = $view->getData()['reports'];

        $this->assertSame(['Guru Nampak'], collect($reports->items())->pluck('name')->all());
    }

    public function test_pasti_report_excludes_guru_named_test(): void
    {
        $admin = $this->createAdmin('master_admin');
        $this->setAuthenticatedUser($admin);

        $pasti = Pasti::query()->create(['name' => 'PASTI UJIAN', 'address' => 'JALAN UJIAN']);

        $testGuru = $this->createGuru($pasti, 'Test', true, '900101-01-9999', '0199999999');
        $realGuru = $this->createGuru($pasti, 'Guru Sebenar', true, '900101-01-8888', '0188888888');

        $this->insertSalary($testGuru->id, now()->subDay(), 1000, 100, 20);
        $this->insertSalary($realGuru->id, now()->subDay(), 1100, 120, 25);

        $view = app(PastiReportController::class)->index();
        $reports = $view->getData()['reports'];

        $this->assertSame(['Guru Sebenar'], collect($reports->items())->pluck('name')->all());
    }

    private function setAuthenticatedUser(User $user): void
    {
        $request = Request::create('/laporan-pasti', 'GET');
        $request->setUserResolver(fn (): User => $user);
        app()->instance('request', $request);
        auth()->setUser($user);
    }

    private function createAdmin(string $roleName): User
    {
        $user = User::query()->create([
            'name' => 'Admin Ujian',
            'nama_samaran' => 'Admin Ujian',
            'email' => uniqid('admin', true) . '@example.test',
        ]);

        $roleId = (int) \DB::table('roles')->where('name', $roleName)->value('id');
        \DB::table('model_has_roles')->insert([
            'role_id' => $roleId,
            'model_type' => User::class,
            'model_id' => $user->id,
        ]);

        return $user;
    }

    private function createGuru(Pasti $pasti, string $name, bool $active, string $kadPengenalan, string $phone): Guru
    {
        return Guru::query()->create([
            'pasti_id' => $pasti->id,
            'name' => $name,
            'email' => strtolower(str_replace(' ', '', $name)) . uniqid() . '@example.test',
            'kad_pengenalan' => $kadPengenalan,
            'phone' => $phone,
            'is_assistant' => false,
            'active' => $active,
        ]);
    }

    private function insertSalary(int $guruId, $completedAt, float $gaji, float $elaun, float $elaunLain): void
    {
        \DB::table('guru_salary_requests')->insert([
            'guru_id' => $guruId,
            'requested_by' => null,
            'requested_at' => now(),
            'completed_by' => null,
            'completed_at' => $completedAt,
            'gaji' => $gaji,
            'elaun' => $elaun,
            'elaun_lain' => $elaunLain,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertPastiInformation(
        int $pastiId,
        $completedAt,
        int $jumlahGuru,
        int $jumlahPembantuGuru,
        int $muridLelaki4Tahun,
        int $muridPerempuan4Tahun,
        int $muridLelaki5Tahun,
        int $muridPerempuan5Tahun,
        int $muridLelaki6Tahun,
        int $muridPerempuan6Tahun
    ): void {
        \DB::table('pasti_information_requests')->insert([
            'pasti_id' => $pastiId,
            'requested_by' => null,
            'requested_at' => now(),
            'completed_by' => null,
            'completed_at' => $completedAt,
            'jumlah_guru' => $jumlahGuru,
            'jumlah_pembantu_guru' => $jumlahPembantuGuru,
            'murid_lelaki_4_tahun' => $muridLelaki4Tahun,
            'murid_perempuan_4_tahun' => $muridPerempuan4Tahun,
            'murid_lelaki_5_tahun' => $muridLelaki5Tahun,
            'murid_perempuan_5_tahun' => $muridPerempuan5Tahun,
            'murid_lelaki_6_tahun' => $muridLelaki6Tahun,
            'murid_perempuan_6_tahun' => $muridPerempuan6Tahun,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
