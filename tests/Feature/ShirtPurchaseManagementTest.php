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
