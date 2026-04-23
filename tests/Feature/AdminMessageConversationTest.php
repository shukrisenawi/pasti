<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureGuruWebOnboardingCompleted;
use App\Models\AdminMessage;
use App\Models\AdminMessageReply;
use App\Models\Guru;
use App\Models\Kawasan;
use App\Models\Pasti;
use App\Models\User;
use App\Notifications\AdminMessageReceivedNotification;
use App\Notifications\AdminMessageReplyNotification;
use App\Services\N8nWebhookService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminMessageConversationTest extends TestCase
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
            $table->date('tarikh_lahir')->nullable();
            $table->date('tarikh_exp_skim_pas')->nullable();
            $table->timestamp('last_login_at')->nullable();
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
            $table->string('dun')->nullable();
            $table->timestamps();
        });

        Schema::create('pastis', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('kawasan_id');
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->timestamps();
        });

        Schema::create('admin_pasti', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('pasti_id');
            $table->timestamps();
        });

        Schema::create('gurus', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->unsignedBigInteger('pasti_id')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('avatar_path')->nullable();
            $table->boolean('is_assistant')->default(false);
            $table->string('phone')->nullable();
            $table->string('marital_status')->nullable();
            $table->string('kursus_guru')->nullable();
            $table->date('joined_at')->nullable();
            $table->boolean('active')->default(true);
            $table->boolean('terima_anugerah')->default(false);
            $table->timestamps();
        });

        Schema::create('admin_messages', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('sender_id');
            $table->string('title');
            $table->text('body');
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
            $table->unsignedBigInteger('sender_id');
            $table->text('body');
            $table->string('image_path')->nullable();
            $table->unsignedBigInteger('deleted_by_id')->nullable();
            $table->timestamp('deleted_at')->nullable();
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

        Schema::create('programs', function (Blueprint $table): void {
            $table->id();
            $table->date('program_date')->nullable();
            $table->timestamps();
        });

        Schema::create('program_teacher', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('guru_id');
            $table->unsignedBigInteger('program_id');
            $table->unsignedBigInteger('program_status_id')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('guru_program', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('guru_id');
            $table->unsignedBigInteger('program_id');
            $table->timestamps();
        });

        Schema::create('pasti_information_requests', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('pasti_id')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('guru_salary_requests', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('guru_id')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('ajk_positions', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('user_ajk_positions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('ajk_position_id');
            $table->unsignedBigInteger('assigned_by')->nullable();
            $table->timestamps();
        });

        Schema::create('kpi_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('guru_id')->nullable();
            $table->timestamps();
        });

        Schema::create('leave_notices', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('guru_id')->nullable();
            $table->date('leave_date')->nullable();
            $table->date('leave_until')->nullable();
            $table->timestamps();
        });

        Schema::create('claims', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('pasti_id')->nullable();
            $table->string('status')->default('pending');
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
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('claims');
        Schema::dropIfExists('leave_notices');
        Schema::dropIfExists('guru_salary_requests');
        Schema::dropIfExists('user_ajk_positions');
        Schema::dropIfExists('ajk_positions');
        Schema::dropIfExists('kpi_snapshots');
        Schema::dropIfExists('pasti_information_requests');
        Schema::dropIfExists('guru_program');
        Schema::dropIfExists('program_teacher');
        Schema::dropIfExists('programs');
        Schema::dropIfExists('admin_message_replies');
        Schema::dropIfExists('admin_message_recipients');
        Schema::dropIfExists('admin_messages');
        Schema::dropIfExists('gurus');
        Schema::dropIfExists('admin_pasti');
        Schema::dropIfExists('pastis');
        Schema::dropIfExists('kawasans');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_admin_can_start_a_direct_conversation_with_one_guru(): void
    {
        Notification::fake();
        $this->mockN8nService();

        [$pasti] = $this->createPastiFixtures();
        $admin = $this->createAdminWithAssignment($pasti);
        $guruUser = $this->createGuruUser($pasti, 'guru1@example.test', 'Cikgu Zara');

        $response = $this->actingAs($admin)->post(route('messages.store'), [
            'conversation_type' => 'direct',
            'body' => 'Salam @nama dari admin.',
            'recipient_user_id' => $guruUser->id,
        ]);

        $message = AdminMessage::query()->first();

        $response->assertRedirect(route('messages.show', $message));

        $this->assertDatabaseHas('admin_messages', [
            'sender_id' => $admin->id,
            'title' => 'Perbualan dengan Cikgu Zara',
            'body' => 'Salam @nama dari admin.',
            'sent_to_all' => false,
        ]);

        $this->assertDatabaseHas('admin_message_recipients', [
            'admin_message_id' => $message->id,
            'user_id' => $guruUser->id,
        ]);

        Notification::assertSentTo($guruUser, AdminMessageReceivedNotification::class);
    }

    public function test_admin_can_send_bulk_conversation_to_selected_gurus(): void
    {
        Notification::fake();
        $n8n = $this->mockN8nService();
        $n8n->shouldReceive('send')
            ->once()
            ->withArgs(function (string $text, ?string $link, ?string $gambar = null): bool {
                return str_contains($text, 'Admin Utama')
                    && str_contains(strtolower($text), 'hebahan')
                    && $link !== null
                    && $gambar === null;
            });

        [$pasti] = $this->createPastiFixtures();
        $admin = $this->createAdminWithAssignment($pasti);
        $guruA = $this->createGuruUser($pasti, 'guruA@example.test', 'Cikgu A');
        $guruB = $this->createGuruUser($pasti, 'guruB@example.test', 'Cikgu B');

        $response = $this->actingAs($admin)->post(route('messages.store'), [
            'conversation_type' => 'bulk',
            'recipient_scope' => 'selected',
            'body' => 'Makluman untuk semua dari @nama.',
            'recipient_user_ids' => [$guruA->id, $guruB->id],
        ]);

        $message = AdminMessage::query()->first();

        $response->assertRedirect(route('messages.show', $message));

        $this->assertDatabaseHas('admin_messages', [
            'sender_id' => $admin->id,
            'title' => 'Hebahan kepada 2 guru',
            'sent_to_all' => false,
        ]);

        $this->assertSame(2, $message->recipients()->count());

        Notification::assertSentTo([$guruA, $guruB], AdminMessageReceivedNotification::class);
    }

    public function test_guru_can_start_a_conversation_to_assigned_admins_and_master_admins(): void
    {
        Notification::fake();
        $n8n = $this->mockN8nService();
        $n8n->shouldReceive('sendGroup2')
            ->once()
            ->withArgs(function (string $text, ?string $link, ?string $gambar = null): bool {
                return str_contains($text, 'Cikgu Murni')
                    && str_contains($text, 'PASTI Al Hikmah')
                    && str_contains(strtolower($text), 'mesej kepada admin')
                    && $link !== null
                    && $gambar === null;
            });

        [$pasti] = $this->createPastiFixtures();
        $masterAdmin = User::query()->create([
            'name' => 'Master Admin',
            'nama_samaran' => 'Master Admin',
            'email' => 'master@example.test',
        ]);
        $this->attachRole($masterAdmin, 'master_admin');

        $assignedAdmin = $this->createAdminWithAssignment($pasti, 'admin-ditugas@example.test', 'Admin Tugasan');
        $guruUser = $this->createGuruUser($pasti, 'guru-start@example.test', 'Cikgu Murni');

        $response = $this->actingAs($guruUser)->post(route('messages.store'), [
            'body' => 'Saya @nama dari @pasti nak bertanya.',
        ]);

        $message = AdminMessage::query()->first();

        $response->assertRedirect(route('messages.show', $message));

        $this->assertDatabaseHas('admin_messages', [
            'sender_id' => $guruUser->id,
            'title' => 'Perbualan PASTI Al Hikmah',
            'body' => 'Saya @nama dari @pasti nak bertanya.',
        ]);

        $this->assertEqualsCanonicalizing(
            [$masterAdmin->id, $assignedAdmin->id],
            $message->recipients()->pluck('users.id')->all()
        );

        Notification::assertSentTo([$masterAdmin, $assignedAdmin], AdminMessageReceivedNotification::class);
    }

    public function test_bulk_reply_notifies_other_participants_except_sender(): void
    {
        Notification::fake();
        $this->mockN8nService();

        [$pasti] = $this->createPastiFixtures();
        $admin = $this->createAdminWithAssignment($pasti);
        $guruA = $this->createGuruUser($pasti, 'guru-bulk-a@example.test', 'Cikgu A');
        $guruB = $this->createGuruUser($pasti, 'guru-bulk-b@example.test', 'Cikgu B');

        $message = AdminMessage::query()->create([
            'sender_id' => $admin->id,
            'title' => 'Hebahan kepada 2 guru',
            'body' => 'Mesej awal',
            'sent_to_all' => false,
        ]);

        $message->recipientLinks()->createMany([
            ['user_id' => $guruA->id],
            ['user_id' => $guruB->id],
        ]);

        $response = $this->actingAs($guruA)->post(route('messages.reply', $message), [
            'body' => 'Saya dah baca @pasti.',
        ]);

        $response->assertRedirect(route('messages.show', $message));

        $reply = AdminMessageReply::query()->first();
        $this->assertSame('Saya dah baca @pasti.', $reply->body);

        Notification::assertSentTo([$admin, $guruB], AdminMessageReplyNotification::class);
        Notification::assertNotSentTo($guruA, AdminMessageReplyNotification::class);
    }

    public function test_admin_bulk_tokens_are_not_applied_when_only_one_guru_is_selected(): void
    {
        Notification::fake();
        $n8n = $this->mockN8nService();
        $n8n->shouldReceive('send')->once();

        [$pasti] = $this->createPastiFixtures();
        $admin = $this->createAdminWithAssignment($pasti);
        $guru = $this->createGuruUser($pasti, 'guru-seorang@example.test', 'Cikgu Solo');

        $response = $this->actingAs($admin)->post(route('messages.store'), [
            'conversation_type' => 'bulk',
            'recipient_scope' => 'selected',
            'body' => 'Makluman dari @nama untuk @pasti.',
            'recipient_user_ids' => [$guru->id],
        ]);

        $message = AdminMessage::query()->latest('id')->first();

        $response->assertRedirect(route('messages.show', $message));
        $this->assertSame('Makluman dari @nama untuk @pasti.', $message->body);
    }

    public function test_messages_index_renders_when_latest_reply_timestamp_comes_from_with_max(): void
    {
        [$pasti] = $this->createPastiFixtures();
        $admin = $this->createAdminWithAssignment($pasti);
        $guru = $this->createGuruUser($pasti, 'guru-index@example.test', 'Cikgu Index');

        $message = AdminMessage::query()->create([
            'sender_id' => $admin->id,
            'title' => 'Hebahan kepada 1 guru',
            'body' => 'Mesej awal',
            'sent_to_all' => false,
        ]);

        $message->recipientLinks()->create([
            'user_id' => $guru->id,
        ]);

        AdminMessageReply::query()->create([
            'admin_message_id' => $message->id,
            'sender_id' => $guru->id,
            'body' => 'Balasan terkini',
        ]);

        $response = $this->actingAs($admin)->get(route('messages.index'));

        $response->assertOk();
        $response->assertSee('Cikgu Index');
        $response->assertSee('Balasan terkini');
    }

    public function test_messages_index_shows_red_inbox_badges_for_unread_messages(): void
    {
        [$pasti] = $this->createPastiFixtures();
        $admin = $this->createAdminWithAssignment($pasti);
        $guru = $this->createGuruUser($pasti, 'guru-badge@example.test', 'Cikgu Badge');

        $message = AdminMessage::query()->create([
            'sender_id' => $admin->id,
            'title' => 'Hebahan Badge',
            'body' => 'Mesej belum dibaca',
            'sent_to_all' => false,
        ]);

        $message->recipientLinks()->create([
            'user_id' => $guru->id,
            'read_at' => null,
        ]);

        $response = $this->actingAs($guru)->get(route('messages.index'));

        $response->assertOk();
        $response->assertSee('rounded-full bg-red-500 px-2 py-0.5 text-[10px] font-bold text-white">1</span>', false);
        $response->assertSee('absolute -right-1 -top-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[10px] text-white">1</span>', false);
    }

    public function test_message_show_uses_scrollable_chat_window_that_sticks_to_latest_message(): void
    {
        [$pasti] = $this->createPastiFixtures();
        $admin = $this->createAdminWithAssignment($pasti);
        $guru = $this->createGuruUser($pasti, 'guru-scroll@example.test', 'Cikgu Scroll');

        $message = AdminMessage::query()->create([
            'sender_id' => $admin->id,
            'title' => 'Perbualan dengan Cikgu Scroll',
            'body' => str_repeat('Mesej panjang ', 40),
            'sent_to_all' => false,
        ]);

        $message->recipientLinks()->create([
            'user_id' => $guru->id,
        ]);

        AdminMessageReply::query()->create([
            'admin_message_id' => $message->id,
            'sender_id' => $guru->id,
            'body' => 'Balasan terbaru',
        ]);

        $response = $this->actingAs($admin)->get(route('messages.show', $message));

        $response->assertOk();
        $response->assertSee('x-ref="chatScroller"', false);
        $response->assertSee('overflow-y-auto', false);
        $response->assertSee('lg:min-h-[400px] lg:max-h-[400px]', false);
        $response->assertSee('x-init="init()"', false);
    }

    public function test_message_show_uses_whatsapp_style_reply_input(): void
    {
        [$pasti] = $this->createPastiFixtures();
        $admin = $this->createAdminWithAssignment($pasti);
        $guru = $this->createGuruUser($pasti, 'guru-input-style@example.test', 'Cikgu Input');

        $message = AdminMessage::query()->create([
            'sender_id' => $admin->id,
            'title' => 'Perbualan dengan Cikgu Input',
            'body' => 'Mesej awal',
            'sent_to_all' => false,
        ]);

        $message->recipientLinks()->create([
            'user_id' => $guru->id,
        ]);

        $response = $this->actingAs($admin)->get(route('messages.show', $message));

        $response->assertOk();
        $response->assertSee('type="text"', false);
        $response->assertSee('aria-label="Pilih emoji"', false);
        $response->assertSee('aria-label="Lampiran"', false);
        $response->assertSee('aria-label="Hantar balasan"', false);
        $response->assertSee('@focus="handleComposerFocus()"', false);
        $response->assertSee('window.visualViewport', false);
    }

    public function test_message_show_displays_chat_time_in_twelve_hour_format(): void
    {
        [$pasti] = $this->createPastiFixtures();
        $admin = $this->createAdminWithAssignment($pasti);
        $guru = $this->createGuruUser($pasti, 'guru-time-format@example.test', 'Cikgu Masa');

        $message = AdminMessage::query()->create([
            'sender_id' => $admin->id,
            'title' => 'Perbualan dengan Cikgu Masa',
            'body' => 'Mesej masa',
            'sent_to_all' => false,
            'created_at' => Carbon::parse('2026-04-23 14:05:00'),
            'updated_at' => Carbon::parse('2026-04-23 14:05:00'),
        ]);

        $message->recipientLinks()->create([
            'user_id' => $guru->id,
        ]);

        $response = $this->actingAs($admin)->get(route('messages.show', $message));

        $response->assertOk();
        $expectedTimestamp = $message->fresh()->created_at->format('g:i A');
        $response->assertSee($expectedTimestamp);
        $this->assertDoesNotMatchRegularExpression('/\d{2}\/\d{2}\/\d{4} \d{1,2}:\d{2} (AM|PM)/', $response->getContent());
    }

    public function test_message_show_displays_full_date_for_chat_from_different_day(): void
    {
        [$pasti] = $this->createPastiFixtures();
        $admin = $this->createAdminWithAssignment($pasti);
        $guru = $this->createGuruUser($pasti, 'guru-old-time-format@example.test', 'Cikgu Lama');

        $message = AdminMessage::query()->create([
            'sender_id' => $admin->id,
            'title' => 'Perbualan dengan Cikgu Lama',
            'body' => 'Mesej lama',
            'sent_to_all' => false,
        ]);

        $message->recipientLinks()->create([
            'user_id' => $guru->id,
        ]);

        $message->forceFill([
            'created_at' => now()->subDay()->setTime(14, 5),
            'updated_at' => now()->subDay()->setTime(14, 5),
        ])->save();

        $response = $this->actingAs($admin)->get(route('messages.show', $message));

        $response->assertOk();
        $expectedTimestamp = $message->fresh()->created_at->format('d/m/Y g:i A');
        $response->assertSee($expectedTimestamp);
    }

    public function test_message_show_uses_mobile_full_height_chat_layout(): void
    {
        [$pasti] = $this->createPastiFixtures();
        $admin = $this->createAdminWithAssignment($pasti);
        $guru = $this->createGuruUser($pasti, 'guru-mobile-layout@example.test', 'Cikgu Mobile');

        $message = AdminMessage::query()->create([
            'sender_id' => $admin->id,
            'title' => 'Perbualan dengan Cikgu Mobile',
            'body' => 'Mesej mobile',
            'sent_to_all' => false,
        ]);

        $message->recipientLinks()->create([
            'user_id' => $guru->id,
        ]);

        $response = $this->actingAs($admin)->get(route('messages.show', $message));

        $response->assertOk();
        $response->assertSee('hidden lg:flex lg:flex-wrap lg:items-center lg:justify-between lg:gap-3', false);
        $response->assertSee('hidden lg:block', false);
        $response->assertSee('gap-0 px-0 py-0 sm:px-6 sm:py-6 lg:gap-6 lg:px-8', false);
        $response->assertSee('min-h-[calc(100dvh-5rem)]', false);
        $response->assertSee('pb-[calc(5.75rem+env(safe-area-inset-bottom))]', false);
        $response->assertSee('h-[calc(100dvh-10.75rem)]', false);
        $response->assertSee('pt-0 pb-0', false);
        $response->assertSee('fixed inset-x-0', false);
        $response->assertSee('bottom-0', false);
    }

    public function test_message_show_offsets_mobile_chat_for_guru_bottom_nav(): void
    {
        [$pasti] = $this->createPastiFixtures();
        $admin = $this->createAdminWithAssignment($pasti);
        $guru = $this->createGuruUser($pasti, 'guru-mobile-offset@example.test', 'Cikgu Offset');

        $message = AdminMessage::query()->create([
            'sender_id' => $admin->id,
            'title' => 'Perbualan dengan Cikgu Offset',
            'body' => 'Mesej mobile guru',
            'sent_to_all' => false,
        ]);

        $message->recipientLinks()->create([
            'user_id' => $guru->id,
        ]);

        $response = $this->actingAs($guru)->get(route('messages.show', $message));

        $response->assertOk();
        $response->assertSee('min-h-[calc(100dvh-9.5rem)]', false);
        $response->assertSee('pb-[calc(8.75rem+env(safe-area-inset-bottom))]', false);
        $response->assertSee('h-[calc(100dvh-15.25rem)] min-h-[calc(100dvh-15.25rem)]', false);
        $response->assertSee('fixed inset-x-0 bottom-[calc(3.75rem+env(safe-area-inset-bottom))] pb-2 z-20', false);
    }

    public function test_message_show_does_not_apply_global_guru_bottom_nav_spacing(): void
    {
        [$pasti] = $this->createPastiFixtures();
        $admin = $this->createAdminWithAssignment($pasti);
        $guru = $this->createGuruUser($pasti, 'guru-no-global-spacing@example.test', 'Cikgu Rapat');

        $message = AdminMessage::query()->create([
            'sender_id' => $admin->id,
            'title' => 'Perbualan dengan Cikgu Rapat',
            'body' => 'Mesej rapat',
            'sent_to_all' => false,
        ]);

        $message->recipientLinks()->create([
            'user_id' => $guru->id,
        ]);

        $response = $this->actingAs($guru)->get(route('messages.show', $message));

        $response->assertOk();
        $response->assertDontSee('guru-main-with-bottom-nav', false);
    }

    public function test_message_show_uses_top_alignment_for_mobile_chat_entries(): void
    {
        [$pasti] = $this->createPastiFixtures();
        $admin = $this->createAdminWithAssignment($pasti);
        $guru = $this->createGuruUser($pasti, 'guru-top-align@example.test', 'Cikgu Atas');

        $message = AdminMessage::query()->create([
            'sender_id' => $admin->id,
            'title' => 'Perbualan dengan Cikgu Atas',
            'body' => 'Mesej atas',
            'sent_to_all' => false,
        ]);

        $message->recipientLinks()->create([
            'user_id' => $guru->id,
        ]);

        $response = $this->actingAs($guru)->get(route('messages.show', $message));

        $response->assertOk();
        $response->assertSee('justify-start space-y-4 pb-2 lg:min-h-[400px] lg:justify-end lg:pb-0', false);
    }

    public function test_message_show_hides_impersonation_alert_on_mobile_layout(): void
    {
        [$pasti] = $this->createPastiFixtures();
        $admin = $this->createAdminWithAssignment($pasti);
        $guru = $this->createGuruUser($pasti, 'guru-impersonate@example.test', 'Cikgu Samaran');

        $message = AdminMessage::query()->create([
            'sender_id' => $admin->id,
            'title' => 'Perbualan dengan Cikgu Samaran',
            'body' => 'Mesej samaran',
            'sent_to_all' => false,
        ]);

        $message->recipientLinks()->create([
            'user_id' => $guru->id,
        ]);

        $response = $this
            ->withSession(['impersonator_user_id' => $admin->id])
            ->actingAs($guru)
            ->get(route('messages.show', $message));

        $response->assertOk();
        $response->assertSee('hidden lg:flex', false);
    }

    public function test_dashboard_view_accepts_latest_message_activity_as_string(): void
    {
        [$pasti] = $this->createPastiFixtures();
        $admin = $this->createAdminWithAssignment($pasti);
        $guru = $this->createGuruUser($pasti, 'guru-dashboard@example.test', 'Cikgu Dashboard');

        $message = AdminMessage::query()->create([
            'sender_id' => $admin->id,
            'title' => 'Hebahan Dashboard',
            'body' => 'Mesej untuk dashboard guru',
            'sent_to_all' => false,
        ]);

        $message->recipientLinks()->create([
            'user_id' => $guru->id,
        ]);

        AdminMessageReply::query()->create([
            'admin_message_id' => $message->id,
            'sender_id' => $admin->id,
            'body' => 'Balasan terkini untuk dashboard',
        ]);

        $message->forceFill([
            'replies_max_created_at' => now()->subMinutes(5)->toDateTimeString(),
        ]);

        view()->share('errors', new \Illuminate\Support\ViewErrorBag());

        $response = $this->actingAs($guru)->view('dashboard', [
            'latestPrograms' => collect(),
            'latestProgram' => null,
            'currentParticipation' => null,
            'statuses' => collect(),
            'canUpdateOwnStatus' => false,
            'topKpiGurus' => collect(),
            'latestYear' => now()->year,
            'latestInboxMessage' => $message,
            'pendingPastiInfoCount' => 0,
            'guruLeaveDays' => 0,
            'guruTeachingDuration' => '-',
            'userAjkPositions' => collect(),
            'adminCashBalance' => 0,
            'adminBankBalance' => 0,
            'birthdayUsers' => collect(),
        ]);

        $response->assertSee('Hebahan Dashboard');
        $response->assertSee('Mesej untuk dashboard guru');
    }

    public function test_message_show_displays_delete_icon_for_entry_owner_and_admin(): void
    {
        [$pasti] = $this->createPastiFixtures();
        $admin = $this->createAdminWithAssignment($pasti);
        $guru = $this->createGuruUser($pasti, 'guru-bubble-owner@example.test', 'Cikgu Bubble');

        $message = AdminMessage::query()->create([
            'sender_id' => $guru->id,
            'title' => 'Perbualan dengan admin',
            'body' => 'Mesej guru',
            'sent_to_all' => false,
        ]);

        $message->recipientLinks()->create([
            'user_id' => $admin->id,
        ]);

        $reply = AdminMessageReply::query()->create([
            'admin_message_id' => $message->id,
            'sender_id' => $guru->id,
            'body' => 'Balasan guru',
        ]);

        $response = $this->actingAs($guru)->get(route('messages.show', $message));

        $response->assertOk();
        $response->assertSee('aria-label="Padam mesej '.$message->id.'"', false);
        $response->assertSee('aria-label="Padam balasan '.$reply->id.'"', false);
    }

    public function test_message_show_does_not_display_delete_icon_for_non_owner_guru(): void
    {
        [$pasti] = $this->createPastiFixtures();
        $admin = $this->createAdminWithAssignment($pasti);
        $guruOwner = $this->createGuruUser($pasti, 'guru-bubble-owner2@example.test', 'Cikgu Owner');
        $guruOther = $this->createGuruUser($pasti, 'guru-bubble-other@example.test', 'Cikgu Lain');

        $message = AdminMessage::query()->create([
            'sender_id' => $admin->id,
            'title' => 'Hebahan kepada 2 guru',
            'body' => 'Mesej admin',
            'sent_to_all' => false,
        ]);

        $message->recipientLinks()->createMany([
            ['user_id' => $guruOwner->id],
            ['user_id' => $guruOther->id],
        ]);

        $reply = AdminMessageReply::query()->create([
            'admin_message_id' => $message->id,
            'sender_id' => $guruOwner->id,
            'body' => 'Balasan owner',
        ]);

        $response = $this->actingAs($guruOther)->get(route('messages.show', $message));

        $response->assertOk();
        $response->assertDontSee('aria-label="Padam mesej '.$message->id.'"', false);
        $response->assertDontSee('aria-label="Padam balasan '.$reply->id.'"', false);
    }

    public function test_admin_can_delete_reply_entry_from_chat(): void
    {
        [$pasti] = $this->createPastiFixtures();
        $admin = $this->createAdminWithAssignment($pasti);
        $guru = $this->createGuruUser($pasti, 'guru-reply-delete@example.test', 'Cikgu Reply');

        $message = AdminMessage::query()->create([
            'sender_id' => $guru->id,
            'title' => 'Perbualan dengan admin',
            'body' => 'Mesej awal',
            'sent_to_all' => false,
        ]);

        $message->recipientLinks()->create([
            'user_id' => $admin->id,
        ]);

        $reply = AdminMessageReply::query()->create([
            'admin_message_id' => $message->id,
            'sender_id' => $guru->id,
            'body' => 'Balasan untuk dipadam',
        ]);

        $response = $this->actingAs($admin)->delete(route('messages.replies.destroy', [$message, $reply]));

        $response->assertRedirect(route('messages.show', $message));
        $this->assertDatabaseHas('admin_message_replies', [
            'id' => $reply->id,
            'deleted_by_id' => $admin->id,
        ]);
        $this->assertDatabaseHas('admin_messages', [
            'id' => $message->id,
        ]);
    }

    public function test_sender_can_delete_message_that_has_not_been_replied_to(): void
    {
        [$pasti] = $this->createPastiFixtures();
        $admin = $this->createAdminWithAssignment($pasti);
        $guru = $this->createGuruUser($pasti, 'guru-delete@example.test', 'Cikgu Padam');

        $message = AdminMessage::query()->create([
            'sender_id' => $admin->id,
            'title' => 'Perbualan dengan Cikgu Padam',
            'body' => 'Mesej belum berbalas',
            'sent_to_all' => false,
        ]);

        $message->recipientLinks()->create([
            'user_id' => $guru->id,
        ]);

        $response = $this->actingAs($admin)->delete(route('messages.destroy', $message));

        $response->assertRedirect(route('messages.show', $message));
        $this->assertDatabaseHas('admin_messages', [
            'id' => $message->id,
            'deleted_by_id' => $admin->id,
        ]);
    }

    public function test_sender_does_not_see_delete_icon_for_message_without_reply(): void
    {
        [$pasti] = $this->createPastiFixtures();
        $admin = $this->createAdminWithAssignment($pasti);
        $guru = $this->createGuruUser($pasti, 'guru-delete-icon@example.test', 'Cikgu Ikon');

        $message = AdminMessage::query()->create([
            'sender_id' => $admin->id,
            'title' => 'Perbualan dengan Cikgu Ikon',
            'body' => 'Mesej belum berbalas',
            'sent_to_all' => false,
        ]);

        $message->recipientLinks()->create([
            'user_id' => $guru->id,
        ]);

        $response = $this->actingAs($admin)->get(route('messages.show', $message));

        $response->assertOk();
        $response->assertDontSee('aria-label="Padam mesej"', false);
    }

    public function test_sender_can_delete_message_even_after_it_has_received_a_reply(): void
    {
        [$pasti] = $this->createPastiFixtures();
        $admin = $this->createAdminWithAssignment($pasti);
        $guru = $this->createGuruUser($pasti, 'guru-delete-lock@example.test', 'Cikgu Kunci');

        $message = AdminMessage::query()->create([
            'sender_id' => $admin->id,
            'title' => 'Perbualan dengan Cikgu Kunci',
            'body' => 'Mesej sudah dibalas',
            'sent_to_all' => false,
        ]);

        $message->recipientLinks()->create([
            'user_id' => $guru->id,
        ]);

        $reply = AdminMessageReply::query()->create([
            'admin_message_id' => $message->id,
            'sender_id' => $guru->id,
            'body' => 'Saya sudah balas.',
        ]);

        $response = $this->actingAs($admin)->delete(route('messages.destroy', $message));

        $response->assertRedirect(route('messages.show', $message));
        $this->assertDatabaseHas('admin_messages', [
            'id' => $message->id,
            'deleted_by_id' => $admin->id,
        ]);
        $this->assertDatabaseHas('admin_message_replies', [
            'admin_message_id' => $message->id,
            'id' => $reply->id,
        ]);
    }

    public function test_deleted_chat_entry_shows_owner_notice_when_owner_deletes_it(): void
    {
        [$pasti] = $this->createPastiFixtures();
        $admin = $this->createAdminWithAssignment($pasti);
        $guru = $this->createGuruUser($pasti, 'guru-notice-owner@example.test', 'Cikgu Owner');

        $message = AdminMessage::query()->create([
            'sender_id' => $guru->id,
            'title' => 'Perbualan dengan admin',
            'body' => 'Mesej owner',
            'sent_to_all' => false,
            'deleted_by_id' => $guru->id,
            'deleted_at' => now(),
        ]);

        $message->recipientLinks()->create([
            'user_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('messages.show', $message));

        $response->assertOk();
        $response->assertSee('Chat ini telah dipadam oleh owner');
    }

    public function test_deleted_chat_entry_shows_admin_notice_when_admin_deletes_it(): void
    {
        [$pasti] = $this->createPastiFixtures();
        $admin = $this->createAdminWithAssignment($pasti);
        $guru = $this->createGuruUser($pasti, 'guru-notice-admin@example.test', 'Cikgu Admin');

        $message = AdminMessage::query()->create([
            'sender_id' => $guru->id,
            'title' => 'Perbualan dengan admin',
            'body' => 'Mesej owner',
            'sent_to_all' => false,
        ]);

        $message->recipientLinks()->create([
            'user_id' => $admin->id,
        ]);

        $reply = AdminMessageReply::query()->create([
            'admin_message_id' => $message->id,
            'sender_id' => $guru->id,
            'body' => 'Balasan owner',
            'deleted_by_id' => $admin->id,
            'deleted_at' => now(),
        ]);

        $response = $this->actingAs($guru)->get(route('messages.show', $message));

        $response->assertOk();
        $response->assertSee('Chat ini telah dipadam oleh admin');
        $response->assertDontSee('Padam balasan '.$reply->id);
    }

    public function test_sender_does_not_see_delete_icon_after_message_has_reply(): void
    {
        [$pasti] = $this->createPastiFixtures();
        $admin = $this->createAdminWithAssignment($pasti);
        $guru = $this->createGuruUser($pasti, 'guru-delete-hide@example.test', 'Cikgu Sorok');

        $message = AdminMessage::query()->create([
            'sender_id' => $admin->id,
            'title' => 'Perbualan dengan Cikgu Sorok',
            'body' => 'Mesej sudah dibalas',
            'sent_to_all' => false,
        ]);

        $message->recipientLinks()->create([
            'user_id' => $guru->id,
        ]);

        AdminMessageReply::query()->create([
            'admin_message_id' => $message->id,
            'sender_id' => $guru->id,
            'body' => 'Balas awal',
        ]);

        $response = $this->actingAs($admin)->get(route('messages.show', $message));

        $response->assertOk();
        $response->assertDontSee('aria-label="Padam mesej"', false);
    }

    /**
     * @return array{0: Pasti}
     */
    private function createPastiFixtures(): array
    {
        $kawasan = Kawasan::query()->create([
            'name' => 'Kawasan Sik',
        ]);

        $pasti = Pasti::query()->create([
            'kawasan_id' => $kawasan->id,
            'name' => 'PASTI Al Hikmah',
        ]);

        return [$pasti];
    }

    private function createAdminWithAssignment(Pasti $pasti, string $email = 'admin@example.test', string $displayName = 'Admin Utama'): User
    {
        $admin = User::query()->create([
            'name' => $displayName,
            'nama_samaran' => $displayName,
            'email' => $email,
        ]);
        $this->attachRole($admin, 'admin');
        $admin->assignedPastis()->sync([$pasti->id]);

        return $admin;
    }

    private function createGuruUser(Pasti $pasti, string $email, string $displayName): User
    {
        $user = User::query()->create([
            'name' => $displayName,
            'nama_samaran' => $displayName,
            'email' => $email,
        ]);
        $this->attachRole($user, 'guru');

        Guru::query()->create([
            'user_id' => $user->id,
            'pasti_id' => $pasti->id,
            'active' => true,
        ]);

        return $user;
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

    private function mockN8nService(): \Mockery\MockInterface
    {
        $mock = \Mockery::mock(N8nWebhookService::class)->shouldIgnoreMissing();
        $mock->shouldReceive('toActionUrl')
            ->zeroOrMoreTimes()
            ->andReturnUsing(fn (?string $url) => $url);
        app()->instance(N8nWebhookService::class, $mock);

        return $mock;
    }
}
