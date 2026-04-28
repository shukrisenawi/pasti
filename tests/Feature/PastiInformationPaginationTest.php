<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureGuruWebOnboardingCompleted;
use App\Http\Controllers\PastiInformationController;
use App\Livewire\PastiInformationIndex;
use App\Models\Kawasan;
use App\Models\Pasti;
use App\Models\User;
use App\Services\N8nWebhookService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
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

    public function test_request_pending_responses_sends_numbered_pasti_list_to_n8n(): void
    {
        $this->seedPastisForReminder();

        $webhookService = \Mockery::mock(N8nWebhookService::class);
        $webhookService->shouldReceive('toActionUrl')
            ->once()
            ->with(\Mockery::on(fn ($url) => is_string($url) && str_contains($url, '/maklumat-pasti')))
            ->andReturn('https://example.test/maklumat-pasti');
        $webhookService->shouldReceive('sendByTemplate')
            ->once()
            ->with(
                N8nWebhookService::KEY_TEXT_PASTI_INFO_RESPONSE_REMINDER,
                ['senarai_pasti' => "1- PASTI Alpha\n2- PASTI Gamma"],
                'https://example.test/maklumat-pasti'
            );

        $this->app->instance(N8nWebhookService::class, $webhookService);

        $admin = $this->adminUserWithAssignedPastis();
        $request = Request::create('/maklumat-pasti/request-reminder', 'POST');
        $request->setUserResolver(fn (): User => $admin);

        $response = app(PastiInformationController::class)->requestPendingResponses($request);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('Mesej telah berjaya dihantar ke group guru.', $response->getSession()->get('status'));
    }

    public function test_send_thanks_sends_thank_you_message_when_all_pasti_completed(): void
    {
        $this->seedPastisForThanks();

        $webhookService = \Mockery::mock(N8nWebhookService::class);
        $webhookService->shouldReceive('toActionUrl')
            ->once()
            ->with(\Mockery::on(fn ($url) => is_string($url) && str_contains($url, '/maklumat-pasti')))
            ->andReturn('https://example.test/maklumat-pasti');
        $webhookService->shouldReceive('sendByTemplate')
            ->once()
            ->with(
                N8nWebhookService::KEY_TEXT_ALL_GURU_COMPLETED_THANKS,
                \Mockery::on(fn (array $variables) => ($variables['perkara'] ?? null) === 'maklumat PASTI' && filled($variables['tarikh'] ?? null)),
                'https://example.test/maklumat-pasti'
            );

        $this->app->instance(N8nWebhookService::class, $webhookService);

        $admin = $this->adminUserWithAssignedPastis();
        $request = Request::create('/maklumat-pasti/send-thanks', 'POST');
        $request->setUserResolver(fn (): User => $admin);

        $response = app(PastiInformationController::class)->sendThanks($request);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('Mesej telah berjaya dihantar ke group guru.', $response->getSession()->get('status'));
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

    private function seedPastisForReminder(): void
    {
        $kawasan = Kawasan::query()->create(['name' => 'Kawasan Sik']);

        foreach (['PASTI Alpha', 'PASTI Beta', 'PASTI Gamma'] as $index => $name) {
            $pasti = Pasti::query()->create([
                'kawasan_id' => $kawasan->id,
                'name' => $name,
            ]);

            \DB::table('pasti_information_requests')->insert([
                'pasti_id' => $pasti->id,
                'requested_by' => null,
                'requested_at' => now()->subHours(3 - $index),
                'completed_by' => null,
                'completed_at' => $name === 'PASTI Beta' ? now() : null,
                'jumlah_guru' => 2,
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
        }
    }

    private function seedPastisForThanks(): void
    {
        $kawasan = Kawasan::query()->create(['name' => 'Kawasan Sik']);

        foreach (['PASTI Alpha', 'PASTI Beta'] as $name) {
            $pasti = Pasti::query()->create([
                'kawasan_id' => $kawasan->id,
                'name' => $name,
            ]);

            \DB::table('pasti_information_requests')->insert([
                'pasti_id' => $pasti->id,
                'requested_by' => null,
                'requested_at' => now()->subHours(2),
                'completed_by' => 1,
                'completed_at' => now(),
                'jumlah_guru' => 2,
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
        }
    }

    private function adminUserWithAssignedPastis(): User
    {
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

        return $admin;
    }
}
