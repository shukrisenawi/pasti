<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureGuruWebOnboardingCompleted;
use App\Models\Guru;
use App\Models\Pasti;
use App\Models\User;
use App\Services\N8nWebhookService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ShirtPurchaseManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('nama_samaran')->nullable();
            $table->string('email')->unique();
            $table->date('tarikh_exp_skim_pas')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->string('password')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('pastis', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('kawasan_id')->nullable();
            $table->string('name');
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

        Schema::create('claims', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('guru_id')->nullable();
            $table->unsignedBigInteger('pasti_id')->nullable();
            $table->date('claim_date')->nullable();
            $table->decimal('amount', 8, 2)->nullable();
            $table->text('notes')->nullable();
            $table->string('image_path')->nullable();
            $table->string('status')->default('pending');
            $table->decimal('approved_amount', 8, 2)->nullable();
            $table->string('payment_method')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('gurus', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('pasti_id')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('default_baju_size')->nullable();
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
            $table->decimal('gaji', 8, 2)->nullable();
            $table->decimal('elaun', 8, 2)->nullable();
            $table->decimal('elaun_transit', 8, 2)->nullable();
            $table->decimal('elaun_lain', 8, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('leave_notices', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('guru_id');
            $table->date('leave_date');
            $table->date('leave_until')->nullable();
            $table->text('reason')->nullable();
            $table->string('mc_image_path')->nullable();
            $table->timestamps();
        });

        Schema::create('kpi_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('guru_id');
            $table->decimal('score', 5, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('programs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('kawasan_id')->nullable();
            $table->unsignedBigInteger('pasti_id')->nullable();
            $table->string('title');
            $table->date('program_date');
            $table->time('program_time')->nullable();
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->string('banner_path')->nullable();
            $table->boolean('require_absence_reason')->default(false);
            $table->unsignedInteger('markah')->default(1);
            $table->unsignedBigInteger('created_by')->nullable();
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

        Schema::create('shirt_purchases', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('sent_to_n8n_at')->nullable();
            $table->timestamp('last_broadcast_at')->nullable();
            $table->timestamps();
        });

        Schema::create('shirt_purchase_responses', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('shirt_purchase_id');
            $table->unsignedBigInteger('guru_id');
            $table->string('size')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->unsignedBigInteger('paid_marked_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamps();
        });

        \DB::table('roles')->insert([
            ['name' => 'master_admin', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'admin', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'guru', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->withoutMiddleware(EnsureGuruWebOnboardingCompleted::class);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('shirt_purchase_responses');
        Schema::dropIfExists('shirt_purchases');
        Schema::dropIfExists('program_teacher');
        Schema::dropIfExists('programs');
        Schema::dropIfExists('kpi_snapshots');
        Schema::dropIfExists('leave_notices');
        Schema::dropIfExists('guru_salary_requests');
        Schema::dropIfExists('pasti_information_requests');
        Schema::dropIfExists('claims');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('admin_message_recipients');
        Schema::dropIfExists('admin_messages');
        Schema::dropIfExists('gurus');
        Schema::dropIfExists('admin_pasti');
        Schema::dropIfExists('pastis');
        Schema::dropIfExists('users');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('roles');

        parent::tearDown();
    }

    public function test_admin_can_create_shirt_purchase_for_assigned_pasti_gurus_only(): void
    {
        $payload = $this->seedAdminAndGurus();
        Storage::fake('public');
        $tempImage = tempnam(sys_get_temp_dir(), 'shirt-purchase-test');
        file_put_contents($tempImage, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9WnSUs8AAAAASUVORK5CYII='));
        $image = new UploadedFile($tempImage, 'baju-korporat.png', 'image/png', null, true);

        $webhookService = \Mockery::mock(N8nWebhookService::class);
        $webhookService->shouldReceive('toActionUrl')->once()->andReturn('https://example.test/pembelian-baju');
        $webhookService->shouldReceive('toPublicUrl')->once()->andReturn('https://example.test/uploads/shirt-purchases/baju-korporat.jpg');
        $webhookService->shouldReceive('sendByTemplate')
            ->once()
            ->with(
                N8nWebhookService::KEY_TEXT_SHIRT_PURCHASE_REQUEST,
                \Mockery::on(fn (array $variables): bool => $variables['tajuk'] === 'Baju Korporat'),
                'https://example.test/pembelian-baju',
                'https://example.test/uploads/shirt-purchases/baju-korporat.jpg'
            );
        $this->app->instance(N8nWebhookService::class, $webhookService);

        $this->actingAs($payload['admin'])
            ->post(route('shirt-purchases.store'), [
                'title' => 'Baju Korporat',
                'description' => 'Sila pilih saiz baju.',
                'image' => $image,
            ])
            ->assertRedirect(route('shirt-purchases.index'));

        $this->assertDatabaseCount('shirt_purchases', 1);
        $storedImagePath = \DB::table('shirt_purchases')->value('image_path');
        $this->assertNotNull($storedImagePath);
        Storage::disk('public')->assertExists($storedImagePath);
        $this->assertDatabaseHas('shirt_purchase_responses', [
            'guru_id' => $payload['eligibleGuru']->id,
            'quantity' => 1,
        ]);
        $this->assertDatabaseMissing('shirt_purchase_responses', [
            'guru_id' => $payload['otherPastiGuru']->id,
        ]);
        $this->assertDatabaseMissing('shirt_purchase_responses', [
            'guru_id' => $payload['assistantGuru']->id,
        ]);
    }

    public function test_guru_response_updates_default_size_and_paid_status(): void
    {
        $payload = $this->seedAdminAndGurus();

        $purchaseId = \DB::table('shirt_purchases')->insertGetId([
            'title' => 'Baju Korporat',
            'description' => 'Sila isi.',
            'created_by' => $payload['admin']->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $responseId = \DB::table('shirt_purchase_responses')->insertGetId([
            'shirt_purchase_id' => $purchaseId,
            'guru_id' => $payload['eligibleGuru']->id,
            'quantity' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($payload['eligibleGuruUser'])
            ->post(route('shirt-purchases.responses.update', $responseId), [
                'size' => 'XL',
                'notes' => 'Potongan slim fit',
                'quantity' => 2,
                'is_paid' => '1',
            ])
            ->assertRedirect(route('shirt-purchases.index'));

        $this->assertDatabaseHas('shirt_purchase_responses', [
            'id' => $responseId,
            'size' => 'XL',
            'quantity' => 2,
            'notes' => 'Potongan slim fit',
        ]);
        $this->assertNotNull(\DB::table('shirt_purchase_responses')->where('id', $responseId)->value('paid_at'));
        $this->assertSame('XL', Guru::query()->findOrFail($payload['eligibleGuru']->id)->default_baju_size);
    }

    public function test_guru_submit_shows_success_alert_on_purchase_list_after_redirect(): void
    {
        $payload = $this->seedAdminAndGurus();

        $purchaseId = \DB::table('shirt_purchases')->insertGetId([
            'title' => 'Baju Korporat',
            'description' => 'Sila isi.',
            'created_by' => $payload['admin']->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $responseId = \DB::table('shirt_purchase_responses')->insertGetId([
            'shirt_purchase_id' => $purchaseId,
            'guru_id' => $payload['eligibleGuru']->id,
            'quantity' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($payload['eligibleGuruUser'])
            ->followingRedirects()
            ->post(route('shirt-purchases.responses.update', $responseId), [
                'size' => 'L',
                'notes' => 'Sila proses',
                'quantity' => 1,
            ])
            ->assertOk()
            ->assertSee('data-testid="shirt-purchase-success-alert"', false)
            ->assertSee('Pembelian baju berjaya dihantar.', false)
            ->assertSee('window.Swal.fire', false);
    }

    public function test_admin_can_mark_paid_and_approve_response(): void
    {
        $payload = $this->seedAdminAndGurus();
        $responseId = $this->seedSubmittedResponse($payload);

        $this->actingAs($payload['admin'])
            ->post(route('shirt-purchases.responses.mark-paid', $responseId))
            ->assertRedirect();

        $this->assertNotNull(\DB::table('shirt_purchase_responses')->where('id', $responseId)->value('paid_at'));

        $this->actingAs($payload['admin'])
            ->post(route('shirt-purchases.responses.approve', $responseId))
            ->assertRedirect();

        $response = \DB::table('shirt_purchase_responses')->where('id', $responseId)->first();
        $this->assertNotNull($response->approved_at);
        $this->assertSame($payload['admin']->id, (int) $response->approved_by);
    }

    public function test_admin_can_mark_paid_without_refresh_using_json_request(): void
    {
        $payload = $this->seedAdminAndGurus();
        $responseId = $this->seedSubmittedResponse($payload);

        $this->actingAs($payload['admin'])
            ->postJson(route('shirt-purchases.responses.mark-paid', $responseId))
            ->assertOk()
            ->assertJson([
                'message' => __('messages.saved'),
                'response' => [
                    'id' => $responseId,
                    'paid' => true,
                    'approved' => false,
                ],
            ]);

        $response = \DB::table('shirt_purchase_responses')->where('id', $responseId)->first();
        $this->assertNotNull($response->paid_at);
        $this->assertSame($payload['admin']->id, (int) $response->paid_marked_by);
    }

    public function test_broadcast_list_only_includes_gurus_with_sizes(): void
    {
        $payload = $this->seedAdminAndGurus();

        $purchaseId = \DB::table('shirt_purchases')->insertGetId([
            'title' => 'Baju Korporat',
            'description' => 'Sila isi.',
            'created_by' => $payload['admin']->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \DB::table('shirt_purchase_responses')->insert([
            [
                'shirt_purchase_id' => $purchaseId,
                'guru_id' => $payload['eligibleGuru']->id,
                'size' => 'M',
                'notes' => null,
                'quantity' => 1,
                'submitted_at' => now(),
                'paid_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'shirt_purchase_id' => $purchaseId,
                'guru_id' => $payload['secondEligibleGuru']->id,
                'size' => null,
                'notes' => null,
                'quantity' => 1,
                'submitted_at' => null,
                'paid_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $webhookService = \Mockery::mock(N8nWebhookService::class);
        $webhookService->shouldReceive('toActionUrl')->once()->andReturn('https://example.test/pembelian-baju/1');
        $webhookService->shouldReceive('sendByTemplate')
            ->once()
            ->with(
                N8nWebhookService::KEY_TEXT_SHIRT_PURCHASE_LIST,
                \Mockery::on(fn (array $variables): bool => $variables['tajuk'] === 'Baju Korporat'
                    && str_contains($variables['senarai'], 'Cikgu A')
                    && ! str_contains($variables['senarai'], 'Cikgu B')),
                'https://example.test/pembelian-baju/1'
            );
        $this->app->instance(N8nWebhookService::class, $webhookService);

        $this->actingAs($payload['admin'])
            ->post(route('shirt-purchases.broadcast', $purchaseId))
            ->assertRedirect();
    }

    public function test_admin_detail_only_shows_gurus_who_have_submitted(): void
    {
        $payload = $this->seedAdminAndGurus();

        $purchaseId = \DB::table('shirt_purchases')->insertGetId([
            'title' => 'Baju Korporat',
            'description' => 'Sila isi.',
            'created_by' => $payload['admin']->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \DB::table('shirt_purchase_responses')->insert([
            [
                'shirt_purchase_id' => $purchaseId,
                'guru_id' => $payload['eligibleGuru']->id,
                'size' => 'L',
                'notes' => 'Sudah isi',
                'quantity' => 1,
                'submitted_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'shirt_purchase_id' => $purchaseId,
                'guru_id' => $payload['secondEligibleGuru']->id,
                'size' => null,
                'notes' => null,
                'quantity' => 1,
                'submitted_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->actingAs($payload['admin'])
            ->get(route('shirt-purchases.show', $purchaseId))
            ->assertOk()
            ->assertSee('Senarai Pembeli')
            ->assertSee('Maklumat Baju')
            ->assertSee('Cikgu A')
            ->assertDontSee('Cikgu B');
    }

    public function test_guru_index_hides_negative_payment_badges(): void
    {
        $payload = $this->seedAdminAndGurus();

        $purchaseId = \DB::table('shirt_purchases')->insertGetId([
            'title' => 'Baju Korporat',
            'description' => 'Sila isi.',
            'created_by' => $payload['admin']->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \DB::table('shirt_purchase_responses')->insert([
            'shirt_purchase_id' => $purchaseId,
            'guru_id' => $payload['eligibleGuru']->id,
            'size' => 'M',
            'quantity' => 1,
            'submitted_at' => now(),
            'paid_at' => null,
            'approved_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($payload['eligibleGuruUser'])
            ->get(route('shirt-purchases.index'))
            ->assertOk()
            ->assertSee('Baju Korporat')
            ->assertSee('Lihat Maklumat')
            ->assertDontSee('Saiz')
            ->assertDontSee('Kuantiti')
            ->assertDontSee('Belum Bayar')
            ->assertDontSee('Belum Approve');
    }

    public function test_guru_can_open_purchase_detail_to_view_and_edit_submitted_information(): void
    {
        $payload = $this->seedAdminAndGurus();

        $purchaseId = \DB::table('shirt_purchases')->insertGetId([
            'title' => 'Baju Korporat',
            'description' => 'Sila isi saiz baju.',
            'created_by' => $payload['admin']->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \DB::table('shirt_purchase_responses')->insert([
            'shirt_purchase_id' => $purchaseId,
            'guru_id' => $payload['eligibleGuru']->id,
            'size' => 'XL',
            'notes' => 'Lengan panjang',
            'quantity' => 2,
            'submitted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($payload['eligibleGuruUser'])
            ->get(route('shirt-purchases.show', $purchaseId))
            ->assertOk()
            ->assertSee('Baju Korporat')
            ->assertSee('Saiz')
            ->assertSee('Kuantiti')
            ->assertSee('Catatan')
            ->assertSee('Lengan panjang');
    }

    private function seedAdminAndGurus(): array
    {
        $admin = User::query()->create([
            'name' => 'Admin Ujian',
            'nama_samaran' => 'Admin Ujian',
            'email' => 'admin'.uniqid().'@example.test',
        ]);
        $this->attachRole($admin, 'admin');

        $assignedPasti = Pasti::query()->create(['name' => 'PASTI A']);
        $otherPasti = Pasti::query()->create(['name' => 'PASTI B']);
        $admin->assignedPastis()->sync([$assignedPasti->id]);

        $eligibleGuruUser = User::query()->create([
            'name' => 'Cikgu A',
            'nama_samaran' => 'Cikgu A',
            'email' => 'guru-a'.uniqid().'@example.test',
        ]);
        $this->attachRole($eligibleGuruUser, 'guru');
        $eligibleGuru = Guru::query()->create([
            'user_id' => $eligibleGuruUser->id,
            'pasti_id' => $assignedPasti->id,
            'name' => 'Cikgu A',
            'email' => $eligibleGuruUser->email,
            'active' => true,
            'is_assistant' => false,
        ]);

        $secondEligibleGuruUser = User::query()->create([
            'name' => 'Cikgu B',
            'nama_samaran' => 'Cikgu B',
            'email' => 'guru-b'.uniqid().'@example.test',
        ]);
        $this->attachRole($secondEligibleGuruUser, 'guru');
        $secondEligibleGuru = Guru::query()->create([
            'user_id' => $secondEligibleGuruUser->id,
            'pasti_id' => $assignedPasti->id,
            'name' => 'Cikgu B',
            'email' => $secondEligibleGuruUser->email,
            'active' => true,
            'is_assistant' => false,
        ]);

        $assistantGuru = Guru::query()->create([
            'pasti_id' => $assignedPasti->id,
            'name' => 'Pembantu A',
            'active' => true,
            'is_assistant' => true,
        ]);

        $otherPastiGuruUser = User::query()->create([
            'name' => 'Cikgu Luar',
            'nama_samaran' => 'Cikgu Luar',
            'email' => 'guru-luar'.uniqid().'@example.test',
        ]);
        $this->attachRole($otherPastiGuruUser, 'guru');
        $otherPastiGuru = Guru::query()->create([
            'user_id' => $otherPastiGuruUser->id,
            'pasti_id' => $otherPasti->id,
            'name' => 'Cikgu Luar',
            'email' => $otherPastiGuruUser->email,
            'active' => true,
            'is_assistant' => false,
        ]);

        return compact(
            'admin',
            'assignedPasti',
            'otherPasti',
            'eligibleGuruUser',
            'eligibleGuru',
            'secondEligibleGuruUser',
            'secondEligibleGuru',
            'assistantGuru',
            'otherPastiGuru'
        );
    }

    private function seedSubmittedResponse(array $payload): int
    {
        $purchaseId = \DB::table('shirt_purchases')->insertGetId([
            'title' => 'Baju Korporat',
            'description' => 'Sila isi.',
            'created_by' => $payload['admin']->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return \DB::table('shirt_purchase_responses')->insertGetId([
            'shirt_purchase_id' => $purchaseId,
            'guru_id' => $payload['eligibleGuru']->id,
            'size' => 'L',
            'quantity' => 1,
            'submitted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
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
