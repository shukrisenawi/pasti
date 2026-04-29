<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureGuruWebOnboardingCompleted;
use App\Http\Controllers\GuruSalaryInformationController;
use App\Models\Guru;
use App\Models\GuruSalaryRequest;
use App\Models\Kawasan;
use App\Models\Pasti;
use App\Models\User;
use App\Services\N8nWebhookService;
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

    public function test_request_pending_responses_sends_numbered_guru_list_to_n8n(): void
    {
        $this->seedPendingGurusForReminder();

        $webhookService = \Mockery::mock(N8nWebhookService::class);
        $webhookService->shouldReceive('toActionUrl')
            ->once()
            ->with(\Mockery::on(fn ($url) => is_string($url) && str_contains($url, '/maklumat-gaji-guru')))
            ->andReturn('https://example.test/maklumat-gaji-guru');
        $webhookService->shouldReceive('sendByTemplate')
            ->once()
            ->with(
                N8nWebhookService::KEY_TEXT_GURU_SALARY_RESPONSE_REMINDER,
                ['senarai_guru' => "1- Ahmad\n2- Nurul\n3- Siti"],
                'https://example.test/maklumat-gaji-guru'
            );

        $this->app->instance(N8nWebhookService::class, $webhookService);

        $this->from('/maklumat-gaji-guru')
            ->actingAs($this->masterAdmin())
            ->post(route('guru-salary-information.request-reminder'))
            ->assertRedirect('/maklumat-gaji-guru')
            ->assertSessionHas('status', 'Mesej telah berjaya dihantar ke group guru.');
    }

    public function test_guru_salary_tabs_separate_pending_and_responded_gurus(): void
    {
        $this->seedGurusForTabs();
        $this->actingAs($this->masterAdmin());

        \Livewire\Livewire::withQueryParams(['tab' => 'pending'])
            ->test(\App\Livewire\GuruSalaryInformationIndex::class)
            ->assertSet('activeTab', 'pending')
            ->assertSee('Guru Pending')
            ->assertSee('Guru Belum Request')
            ->assertDontSee('Guru Responded')
            ->call('switchTab', 'responded')
            ->assertSet('activeTab', 'responded')
            ->assertSee('Guru Responded')
            ->assertDontSee('Guru Pending')
            ->assertDontSee('Guru Belum Request');
    }

    public function test_menu_badge_counts_unique_pending_gurus_only(): void
    {
        $this->seedPendingGurusForReminder();

        $ahmad = Guru::query()->where('name', 'Ahmad')->firstOrFail();
        \DB::table('guru_salary_requests')->insert([
            'guru_id' => $ahmad->id,
            'requested_by' => null,
            'requested_at' => now()->subHours(2),
            'completed_by' => null,
            'completed_at' => null,
            'gaji' => null,
            'elaun' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach (['Nur Famiza Fazilah', 'Roslina Lahman'] as $name) {
            $user = User::query()->create([
                'name' => $name,
                'nama_samaran' => $name,
                'email' => strtolower(str_replace(' ', '', $name)) . uniqid() . '@example.test',
            ]);
            $this->attachRole($user, 'guru');

            $guru = Guru::query()->create([
                'user_id' => $user->id,
                'pasti_id' => Pasti::query()->where('name', $name === 'Nur Famiza Fazilah' ? 'AL-FALAH' : 'AL-FURQAN')->value('id'),
                'name' => $name,
                'email' => $user->email,
                'is_assistant' => false,
                'active' => false,
            ]);

            \DB::table('guru_salary_requests')->insert([
                'guru_id' => $guru->id,
                'requested_by' => null,
                'requested_at' => now()->subHours(3),
                'completed_by' => null,
                'completed_at' => null,
                'gaji' => null,
                'elaun' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $count = Guru::query()
            ->where('is_assistant', false)
            ->where('active', true)
            ->whereNotNull('user_id')
            ->whereDoesntHave('user', function ($query): void {
                $query->where(function ($nameQuery): void {
                    $nameQuery
                        ->whereRaw('lower(coalesce(name, \'\')) = ?', ['test'])
                        ->orWhereRaw('lower(coalesce(nama_samaran, \'\')) = ?', ['test']);
                });
            })
            ->whereHas('salaryRequests', function ($query): void {
                $query->whereNull('completed_at');
            })
            ->count();

        $this->assertSame(3, $count);
    }

    public function test_update_last_guru_salary_request_sends_auto_thanks_when_all_completed(): void
    {
        $payload = $this->seedCompletedGurusForAutoThanks();

        $webhookService = \Mockery::mock(N8nWebhookService::class);
        $webhookService->shouldReceive('toActionUrl')
            ->twice()
            ->with(\Mockery::on(fn ($url) => is_string($url) && str_contains($url, '/maklumat-gaji-guru')))
            ->andReturn('https://example.test/maklumat-gaji-guru');
        $webhookService->shouldReceive('sendGroup2ByTemplate')
            ->once();
        $webhookService->shouldReceive('sendByTemplate')
            ->once()
            ->with(
                N8nWebhookService::KEY_TEXT_ALL_GURU_COMPLETED_THANKS,
                ['perkara' => 'maklumat gaji guru'],
                'https://example.test/maklumat-gaji-guru'
            );

        $this->app->instance(N8nWebhookService::class, $webhookService);

        $request = Request::create('/maklumat-gaji-guru/' . $payload['request']->id . '/isi', 'POST', [
            'gaji' => 1200,
            'elaun' => 150,
        ]);
        $request->setUserResolver(fn (): User => $payload['user']);

        $response = app(GuruSalaryInformationController::class)->update($request, $payload['request']);

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

    private function seedPendingGurusForReminder(): void
    {
        $kawasan = Kawasan::query()->create(['name' => 'Kawasan Sik']);
        $pasti = Pasti::query()->create([
            'kawasan_id' => $kawasan->id,
            'name' => 'PASTI Alpha',
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

            \DB::table('guru_salary_requests')->insert([
                'guru_id' => $guru->id,
                'requested_by' => null,
                'requested_at' => now()->subDay(),
                'completed_by' => null,
                'completed_at' => null,
                'gaji' => null,
                'elaun' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function seedGurusForTabs(): void
    {
        $kawasan = Kawasan::query()->create(['name' => 'Kawasan Sik']);
        $pasti = Pasti::query()->create([
            'kawasan_id' => $kawasan->id,
            'name' => 'PASTI Tab',
        ]);

        foreach ([
            ['name' => 'Guru Responded', 'completed_at' => now()->subDay(), 'completed' => true],
            ['name' => 'Guru Pending', 'completed_at' => null, 'completed' => false],
            ['name' => 'Guru Belum Request', 'completed_at' => null, 'completed' => null],
        ] as $index => $item) {
            $user = User::query()->create([
                'name' => $item['name'],
                'nama_samaran' => $item['name'],
                'email' => strtolower(str_replace(' ', '', $item['name'])) . uniqid() . '@example.test',
            ]);
            $this->attachRole($user, 'guru');

            $guru = Guru::query()->create([
                'user_id' => $user->id,
                'pasti_id' => $pasti->id,
                'name' => $item['name'],
                'email' => $user->email,
                'is_assistant' => false,
                'active' => true,
            ]);

            if ($item['completed'] === true) {
                \DB::table('guru_salary_requests')->insert([
                    'guru_id' => $guru->id,
                    'requested_by' => null,
                    'requested_at' => now()->subDays(2),
                    'completed_by' => $user->id,
                    'completed_at' => $item['completed_at'],
                    'gaji' => 1000,
                    'elaun' => 100,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } elseif ($item['completed'] === false) {
                \DB::table('guru_salary_requests')->insert([
                    'guru_id' => $guru->id,
                    'requested_by' => null,
                    'requested_at' => now()->subHours(4),
                    'completed_by' => null,
                    'completed_at' => null,
                    'gaji' => null,
                    'elaun' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function seedCompletedGurusForAutoThanks(): array
    {
        $kawasan = Kawasan::query()->create(['name' => 'Kawasan Sik']);
        $pasti = Pasti::query()->create([
            'kawasan_id' => $kawasan->id,
            'name' => 'PASTI Alpha',
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
                'pasti_id' => $pasti->id,
                'name' => $name,
                'email' => $user->email,
                'is_assistant' => false,
                'active' => true,
            ]);

            \DB::table('guru_salary_requests')->insert([
                'guru_id' => $guru->id,
                'requested_by' => null,
                'requested_at' => now()->subDays(4 - $index),
                'completed_by' => $index < 3 ? $user->id : null,
                'completed_at' => $index < 3 ? now()->subDays(4 - $index) : null,
                'gaji' => $index < 3 ? 1000 + ($index * 100) : null,
                'elaun' => $index < 3 ? 100 + ($index * 10) : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $pendingUser = User::query()->where('name', 'Nurul')->firstOrFail();
        $pendingRequest = GuruSalaryRequest::query()->whereHas('guru', fn ($q) => $q->where('name', 'Nurul'))->firstOrFail();

        return [
            'user' => $pendingUser,
            'request' => $pendingRequest,
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
