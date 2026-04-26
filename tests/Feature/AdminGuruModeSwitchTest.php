<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureGuruWebOnboardingCompleted;
use App\Models\Guru;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminGuruModeSwitchTest extends TestCase
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
            $table->string('locale')->nullable();
            $table->string('admin_assignment_scope')->nullable();
            $table->string('avatar_path')->nullable();
            $table->boolean('force_password_change')->default(false);
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

        \DB::table('roles')->insert([
            ['name' => 'master_admin', 'guard_name' => 'web'],
            ['name' => 'admin', 'guard_name' => 'web'],
            ['name' => 'guru', 'guard_name' => 'web'],
        ]);

        $this->withoutMiddleware(EnsureGuruWebOnboardingCompleted::class);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('gurus');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_admin_with_guru_role_can_switch_to_guru_mode_after_admin_login(): void
    {
        $user = $this->createAdminGuruUser('switch@example.test');

        $response = $this->actingAs($user)
            ->withSession(['login_using_admin_role' => true])
            ->post(route('impersonation.switch-to-guru-mode'));

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('active_role_mode', 'guru');
        $response->assertSessionHas('status', 'Anda kini melihat sistem sebagai guru.');
    }

    public function test_guru_mode_blocks_admin_routes_and_can_switch_back_to_admin_mode(): void
    {
        $user = $this->createAdminGuruUser('switch-back@example.test');

        $blockedResponse = $this->actingAs($user)
            ->withSession([
                'login_using_admin_role' => true,
                'active_role_mode' => 'guru',
            ])
            ->get(route('users.gurus.index'));

        $blockedResponse->assertRedirect(route('dashboard'));
        $blockedResponse->assertSessionHas('status', 'Akses halaman admin tidak tersedia semasa anda sedang dalam mod guru.');

        $switchBackResponse = $this->actingAs($user)
            ->withSession([
                'login_using_admin_role' => true,
                'active_role_mode' => 'guru',
            ])
            ->post(route('impersonation.switch-to-admin-mode'));

        $switchBackResponse->assertRedirect(route('dashboard'));
        $switchBackResponse->assertSessionMissing('active_role_mode');
        $switchBackResponse->assertSessionHas('status', 'Kembali semula ke mod admin.');
    }

    public function test_guru_cannot_switch_modes_without_admin_login_context(): void
    {
        $user = $this->createAdminGuruUser('no-admin-login@example.test');

        $response = $this->actingAs($user)->post(route('impersonation.switch-to-guru-mode'));

        $response->assertForbidden();
    }

    public function test_admin_without_guru_role_but_same_email_is_assigned_role_on_login(): void
    {
        // 1. Create admin user without guru role
        $admin = User::query()->create([
            'name' => 'Admin Only',
            'email' => 'admin-sync@example.test',
            'password' => \Hash::make('password'),
        ]);
        $this->attachRole($admin, 'admin');

        // 2. Create guru record with same email
        Guru::query()->create([
            'name' => 'Guru Profile',
            'email' => 'admin-sync@example.test',
        ]);

        $this->assertFalse($admin->hasRole('guru'));

        // 3. Login as admin
        $response = $this->post('/login', [
            'email' => 'admin-sync@example.test',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));

        // 4. Verify role is assigned and user_id is linked
        $admin->refresh();
        $this->assertTrue($admin->hasRole('guru'));
        $this->assertEquals($admin->id, Guru::where('email', 'admin-sync@example.test')->value('user_id'));
        
        // 5. Verify can switch
        $this->assertTrue($admin->canSwitchToGuruMode());
    }

    private function createAdminGuruUser(string $email): User
    {
        $user = User::query()->create([
            'name' => 'Admin Guru',
            'nama_samaran' => 'Admin Guru',
            'email' => $email,
        ]);

        $this->attachRole($user, 'admin');
        $this->attachRole($user, 'guru');

        Guru::query()->create([
            'user_id' => $user->id,
            'name' => 'Admin Guru',
            'email' => $email,
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
}
