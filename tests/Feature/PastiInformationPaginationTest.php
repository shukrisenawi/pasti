<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureGuruWebOnboardingCompleted;
use App\Http\Controllers\PastiInformationController;
use App\Livewire\PastiInformationIndex;
use App\Models\Guru;
use App\Models\Kawasan;
use App\Models\Pasti;
use App\Models\PastiInformationRequest;
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

        $this->withoutMiddleware(EnsureGuruWebOnboardingCompleted::class);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('pasti_information_requests');
        Schema::dropIfExists('program_teacher');
        Schema::dropIfExists('pastis');
        Schema::dropIfExists('gurus');
        Schema::dropIfExists('admin_pasti');
        Schema::dropIfExists('admin_message_recipients');
        Schema::dropIfExists('admin_messages');
        Schema::dropIfExists('notifications');
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
            'pasti_id' => Pasti::query()->where('name', 'PASTI Ujian 10')->value('id'),
            'requested_by' => null,
            'requested_at' => now()->subHour(),
            'completed_by' => null,
            'completed_at' => now()->subHour(),
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

        Livewire::withQueryParams(['tab' => 'responded'])
            ->test(PastiInformationIndex::class)
            ->assertSet('activeTab', 'responded')
            ->assertSeeInOrder(['PASTI Ujian 03', 'PASTI Ujian 10']);
    }

    public function test_pasti_information_tabs_separate_pending_and_responded_pastis(): void
    {
        $this->seedPastisForReminder();
        $this->actingAs($this->masterAdmin());

        Livewire::test(PastiInformationIndex::class)
            ->assertSet('activeTab', 'pending')
            ->assertSee('PASTI Alpha')
            ->assertSee('PASTI Gamma')
            ->assertDontSee('PASTI Beta')
            ->call('switchTab', 'responded')
            ->assertSet('activeTab', 'responded')
            ->assertSee('PASTI Beta')
            ->assertDontSee('PASTI Alpha')
            ->assertDontSee('PASTI Gamma');
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

    public function test_menu_badge_for_pasti_information_ignores_test_account(): void
    {
        $this->seedPastisForMenuBadgeCount();
        $count = PastiInformationRequest::query()
            ->whereNull('completed_at')
            ->whereDoesntHave('pasti.gurus.user', function ($query): void {
                $query->where(function ($nameQuery): void {
                    $nameQuery
                        ->whereRaw('lower(coalesce(name, \'\')) = ?', ['test'])
                        ->orWhereRaw('lower(coalesce(nama_samaran, \'\')) = ?', ['test']);
                });
            })
            ->count();

        $this->assertSame(1, $count);
    }

    public function test_update_last_pasti_request_sends_auto_thanks_when_all_completed(): void
    {
        $payload = $this->seedPastisForAutoThanks();

        $webhookService = \Mockery::mock(N8nWebhookService::class);
        $webhookService->shouldReceive('toActionUrl')
            ->twice()
            ->with(\Mockery::on(fn ($url) => is_string($url) && str_contains($url, '/maklumat-pasti')))
            ->andReturn('https://example.test/maklumat-pasti');
        $webhookService->shouldReceive('sendGroup2ByTemplate')
            ->once();
        $webhookService->shouldReceive('sendByTemplate')
            ->once()
            ->with(
                N8nWebhookService::KEY_TEXT_ALL_GURU_COMPLETED_THANKS,
                ['perkara' => 'maklumat PASTI'],
                'https://example.test/maklumat-pasti'
            );

        $this->app->instance(N8nWebhookService::class, $webhookService);

        $request = Request::create('/maklumat-pasti/' . $payload['request']->id . '/isi', 'POST', [
            'jumlah_guru' => 3,
            'jumlah_pembantu_guru' => 1,
            'murid_lelaki_4_tahun' => 5,
            'murid_perempuan_4_tahun' => 4,
            'murid_lelaki_5_tahun' => 6,
            'murid_perempuan_5_tahun' => 7,
            'murid_lelaki_6_tahun' => 8,
            'murid_perempuan_6_tahun' => 9,
        ]);
        $request->setUserResolver(fn (): User => $payload['user']);

        $response = app(PastiInformationController::class)->update($request, $payload['request']);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('Data berjaya disimpan.', $response->getSession()->get('status'));
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

    private function masterAdmin(): User
    {
        $user = User::query()->create([
            'name' => 'Master Admin',
            'nama_samaran' => 'Master Admin',
            'email' => 'master'.uniqid().'@example.test',
        ]);
        $this->attachRole($user, 'master_admin');

        return $user;
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

    private function seedPastisForAutoThanks(): array
    {
        $kawasan = Kawasan::query()->create(['name' => 'Kawasan Sik']);
        $pastiComplete = Pasti::query()->create([
            'kawasan_id' => $kawasan->id,
            'name' => 'PASTI Alpha',
        ]);
        $pastiPending = Pasti::query()->create([
            'kawasan_id' => $kawasan->id,
            'name' => 'PASTI Beta',
        ]);

        $completeUser = User::query()->create([
            'name' => 'Guru Lengkap',
            'nama_samaran' => 'Guru Lengkap',
            'email' => 'lengkap'.uniqid().'@example.test',
        ]);
        $this->attachRole($completeUser, 'guru');
        Guru::query()->create([
            'user_id' => $completeUser->id,
            'pasti_id' => $pastiComplete->id,
        ]);

        $pendingUser = User::query()->create([
            'name' => 'Guru Pending',
            'nama_samaran' => 'Guru Pending',
            'email' => 'pending'.uniqid().'@example.test',
        ]);
        $this->attachRole($pendingUser, 'guru');
        Guru::query()->create([
            'user_id' => $pendingUser->id,
            'pasti_id' => $pastiPending->id,
        ]);

        PastiInformationRequest::query()->insert([
            [
                'pasti_id' => $pastiComplete->id,
                'requested_by' => null,
                'requested_at' => now()->subHours(2),
                'completed_by' => $completeUser->id,
                'completed_at' => now()->subHour(),
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
            ],
            [
                'pasti_id' => $pastiPending->id,
                'requested_by' => null,
                'requested_at' => now()->subHour(),
                'completed_by' => null,
                'completed_at' => null,
                'jumlah_guru' => null,
                'jumlah_pembantu_guru' => null,
                'murid_lelaki_4_tahun' => null,
                'murid_perempuan_4_tahun' => null,
                'murid_lelaki_5_tahun' => null,
                'murid_perempuan_5_tahun' => null,
                'murid_lelaki_6_tahun' => null,
                'murid_perempuan_6_tahun' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        return [
            'user' => $pendingUser,
            'request' => PastiInformationRequest::query()->where('pasti_id', $pastiPending->id)->firstOrFail(),
        ];
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

    private function seedPastisForMenuBadgeCount(): void
    {
        $kawasan = Kawasan::query()->create(['name' => 'Kawasan Sik']);

        $testPasti = Pasti::query()->create([
            'kawasan_id' => $kawasan->id,
            'name' => 'PASTI Test',
        ]);

        $realPasti = Pasti::query()->create([
            'kawasan_id' => $kawasan->id,
            'name' => 'PASTI Benar',
        ]);

        foreach ([
            ['pasti' => $testPasti, 'guru_name' => 'Test'],
            ['pasti' => $realPasti, 'guru_name' => 'Guru Benar'],
        ] as $item) {
            $user = User::query()->create([
                'name' => $item['guru_name'],
                'nama_samaran' => $item['guru_name'],
                'email' => strtolower(str_replace(' ', '', $item['guru_name'])).uniqid().'@example.test',
            ]);
            $this->attachRole($user, 'guru');

            Guru::query()->create([
                'user_id' => $user->id,
                'pasti_id' => $item['pasti']->id,
            ]);

            PastiInformationRequest::query()->create([
                'pasti_id' => $item['pasti']->id,
                'requested_by' => null,
                'requested_at' => now()->subHour(),
                'completed_by' => null,
                'completed_at' => null,
                'jumlah_guru' => null,
                'jumlah_pembantu_guru' => null,
                'murid_lelaki_4_tahun' => null,
                'murid_perempuan_4_tahun' => null,
                'murid_lelaki_5_tahun' => null,
                'murid_perempuan_5_tahun' => null,
                'murid_lelaki_6_tahun' => null,
                'murid_perempuan_6_tahun' => null,
            ]);
        }
    }
}
