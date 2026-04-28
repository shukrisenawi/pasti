<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureGuruWebOnboardingCompleted;
use App\Livewire\PastiInformationIndex;
use App\Models\Kawasan;
use App\Models\Pasti;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class PastiInformationPaginationTest extends TestCase
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

        Schema::create('gurus', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('pasti_id')->nullable();
            $table->timestamps();
        });

        Schema::create('admin_pasti', function (Blueprint $table): void {
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('pasti_id');
            $table->timestamps();
        });

        Schema::create('pastis', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('kawasan_id');
            $table->string('name');
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
            ['name' => 'guru', 'guard_name' => 'web'],
        ]);

        $this->withoutMiddleware(EnsureGuruWebOnboardingCompleted::class);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('pasti_information_requests');
        Schema::dropIfExists('pastis');
        Schema::dropIfExists('gurus');
        Schema::dropIfExists('admin_pasti');
        Schema::dropIfExists('kawasans');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_pasti_information_page_renders_first_page_records_by_default(): void
    {
        $this->seedPastisForPagination();

        Livewire::test(PastiInformationIndex::class)
            ->assertSee('PASTI Ujian 10')
            ->assertDontSee('PASTI Ujian 01');
    }

    public function test_pasti_information_page_ignores_default_page_query_string(): void
    {
        $this->seedPastisForPagination();

        Livewire::withQueryParams(['page' => 2])
            ->test(PastiInformationIndex::class)
            ->assertSee('PASTI Ujian 10')
            ->assertDontSee('PASTI Ujian 01');
    }

    public function test_pasti_information_page_uses_dedicated_pagination_query_string(): void
    {
        $this->seedPastisForPagination();

        Livewire::withQueryParams(['pastiInfoPage' => 2])
            ->test(PastiInformationIndex::class)
            ->assertSee('PASTI Ujian 01')
            ->assertDontSee('PASTI Ujian 10');
    }

    public function test_pasti_information_page_orders_by_latest_completed_response_first(): void
    {
        $this->seedPastisForPagination();

        \DB::table('pasti_information_requests')->insert([
            'pasti_id' => Pasti::query()->where('name', 'PASTI Ujian 03')->value('id'),
            'requested_by' => null,
            'requested_at' => now()->subHour(),
            'completed_by' => null,
            'completed_at' => now()->addHour(),
            'jumlah_guru' => 1,
            'jumlah_pembantu_guru' => 1,
            'murid_lelaki_4_tahun' => 1,
            'murid_perempuan_4_tahun' => 1,
            'murid_lelaki_5_tahun' => 1,
            'murid_perempuan_5_tahun' => 1,
            'murid_lelaki_6_tahun' => 1,
            'murid_perempuan_6_tahun' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Livewire::test(PastiInformationIndex::class)
            ->assertSeeInOrder(['PASTI Ujian 03', 'PASTI Ujian 10']);
    }

    public function test_admin_pasti_information_page_uses_dedicated_pagination_query_string(): void
    {
        $this->seedPastisForPagination();

        $admin = User::query()->create([
            'name' => 'Admin',
            'nama_samaran' => 'Admin',
            'email' => 'admin'.uniqid().'@example.test',
        ]);
        $this->attachRole($admin, 'admin');

        $pastiIds = Pasti::query()->pluck('id')->all();
        foreach ($pastiIds as $pastiId) {
            \DB::table('admin_pasti')->insert([
                'user_id' => $admin->id,
                'pasti_id' => $pastiId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->actingAs($admin);

        Livewire::withQueryParams(['pastiInfoPage' => 2])
            ->test(PastiInformationIndex::class)
            ->assertSee('PASTI Ujian 01')
            ->assertDontSee('PASTI Ujian 10');
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

    private function seedPastisForPagination(): void
    {
        $user = User::query()->create([
            'name' => 'Master Admin',
            'nama_samaran' => 'Master Admin',
            'email' => 'master'.uniqid().'@example.test',
        ]);
        $this->attachRole($user, 'master_admin');

        $kawasan = Kawasan::query()->create(['name' => 'Kawasan Sik']);

        foreach (range(1, 10) as $number) {
            Pasti::query()->create([
                'kawasan_id' => $kawasan->id,
                'name' => sprintf('PASTI Ujian %02d', $number),
            ]);
        }

        $this->actingAs($user);
    }
}
