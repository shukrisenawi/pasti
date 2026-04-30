<?php

namespace Tests\Feature;

use App\Models\Guru;
use App\Models\Kawasan;
use App\Models\Pasti;
use App\Models\User;
use App\Support\GuruProfileCompletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }

    public function test_password_onboarding_step_shows_only_password_form_without_tabs(): void
    {
        Role::findOrCreate('guru');

        $user = User::factory()->create([
            'nama_samaran' => 'Guru Ujian',
            'tarikh_lahir' => '1990-01-01',
            'avatar_path' => 'avatars/guru-ujian.jpg',
            'password' => Hash::make(GuruProfileCompletionService::DEFAULT_GURU_PASSWORD),
            'force_password_change' => true,
        ]);
        $user->assignRole('guru');

        $kawasan = Kawasan::query()->create([
            'name' => 'Kawasan Ujian',
            'code' => 'KUJ',
        ]);

        $pasti = Pasti::query()->create([
            'kawasan_id' => $kawasan->id,
            'name' => 'PASTI Ujian',
            'code' => 'PUJ',
        ]);

        Guru::query()->create([
            'user_id' => $user->id,
            'pasti_id' => $pasti->id,
            'name' => $user->name,
            'phone' => '0123456789',
            'marital_status' => 'married',
            'joined_at' => '2024-01-01',
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/profile?step=password');

        $response
            ->assertOk()
            ->assertDontSee('data-testid="profile-tab-switcher"', false)
            ->assertDontSee('data-testid="profile-tab-panel"', false)
            ->assertSee('data-testid="password-tab-panel"', false)
            ->assertSee('Update Password');
    }

    public function test_guru_profile_requires_identity_card_and_saves_it(): void
    {
        Role::findOrCreate('guru');

        $user = User::factory()->create([
            'nama_samaran' => 'Guru Profil',
            'tarikh_lahir' => '1990-01-01',
            'avatar_path' => 'avatars/guru-profil.jpg',
        ]);
        $user->assignRole('guru');

        $guru = Guru::query()->create([
            'user_id' => $user->id,
            'name' => $user->name,
        ]);

        $this->actingAs($user)
            ->from('/profile')
            ->patch('/profile', [
                'name' => 'Guru Profil',
                'nama_samaran' => 'Guru Profil',
                'email' => $user->email,
                'tarikh_lahir' => '1990-01-01',
                'phone' => '0123456789',
                'marital_status' => 'married',
                'joined_at' => '2024-01-01',
                'kursus_guru' => 'semester_1',
            ])
            ->assertSessionHasErrors('kad_pengenalan');

        $this->actingAs($user)
            ->patch('/profile', [
                'name' => 'Guru Profil',
                'nama_samaran' => 'Guru Profil',
                'email' => $user->email,
                'tarikh_lahir' => '1990-01-01',
                'phone' => '0123456789',
                'kad_pengenalan' => '900101-01-1234',
                'marital_status' => 'married',
                'joined_at' => '2024-01-01',
                'kursus_guru' => 'semester_1',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('900101-01-1234', $guru->fresh()->kad_pengenalan);
    }
}
