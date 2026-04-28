<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureGuruWebOnboardingCompleted;
use App\Http\Controllers\GuruSalaryInformationController;
use App\Models\Guru;
use App\Models\Kawasan;
use App\Models\Pasti;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GuruSalaryInformationSortingTest extends TestCase
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
            $table->unsignedBigInteger('kawasan_id');
            $table->string('name');
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
        Schema::dropIfExists('guru_salary_requests');
        Schema::dropIfExists('gurus');
        Schema::dropIfExists('pastis');
        Schema::dropIfExists('admin_pasti');
        Schema::dropIfExists('kawasans');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_guru_salary_page_orders_by_latest_completed_response_first(): void
    {
        $this->seedGurusForSorting();

        $request = Request::create('/maklumat-gaji-guru', 'GET');
        $request->setUserResolver(fn (): User => $this->masterAdmin());

        $view = app(GuruSalaryInformationController::class)->index($request);
        $gurus = $view->getData()['gurus'];

        $this->assertSame(
            ['Guru Baru', 'Guru Tengah', 'Guru Lama'],
            collect($gurus->items())->pluck('display_name')->all()
        );
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

    private function seedGurusForSorting(): void
    {
        $kawasan = Kawasan::query()->create(['name' => 'Kawasan Sik']);

        $pastis = [];
        foreach (['PASTI Alpha', 'PASTI Beta', 'PASTI Gamma'] as $name) {
            $pastis[] = Pasti::query()->create([
                'kawasan_id' => $kawasan->id,
                'name' => $name,
            ]);
        }

        $gurus = [];
        foreach (['Guru Lama', 'Guru Tengah', 'Guru Baru'] as $index => $name) {
            $user = User::query()->create([
                'name' => $name,
                'nama_samaran' => $name,
                'email' => strtolower(str_replace(' ', '', $name)).uniqid().'@example.test',
            ]);

            $gurus[$index] = Guru::query()->create([
                'user_id' => $user->id,
                'pasti_id' => $pastis[$index]->id,
                'name' => $name,
                'email' => $user->email,
                'is_assistant' => false,
                'active' => true,
            ]);
        }

        foreach ($gurus as $index => $guru) {
            \DB::table('guru_salary_requests')->insert([
                'guru_id' => $guru->id,
                'requested_by' => null,
                'requested_at' => now()->subDays(3 - $index),
                'completed_by' => null,
                'completed_at' => now()->subDays(3 - $index),
                'gaji' => 1000 + ($index * 100),
                'elaun' => 100 + ($index * 10),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        \DB::table('guru_salary_requests')
            ->where('guru_id', $gurus[0]->id)
            ->update(['completed_at' => now()->subDay()]);

        \DB::table('guru_salary_requests')
            ->where('guru_id', $gurus[1]->id)
            ->update(['completed_at' => now()]);

        \DB::table('guru_salary_requests')
            ->where('guru_id', $gurus[2]->id)
            ->update(['completed_at' => now()->addDay()]);
    }
}
