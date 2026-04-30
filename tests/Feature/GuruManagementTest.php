<?php

namespace Tests\Feature;

use App\Models\Guru;
use App\Models\Kawasan;
use App\Models\Pasti;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GuruManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cannot_store_main_guru_without_identity_card(): void
    {
        Role::findOrCreate('master_admin');

        $admin = User::factory()->create();
        $admin->assignRole('master_admin');

        $kawasan = Kawasan::query()->create([
            'name' => 'Kawasan Ujian',
            'code' => 'KUJ',
        ]);

        $pasti = Pasti::query()->create([
            'kawasan_id' => $kawasan->id,
            'name' => 'PASTI Ujian',
            'code' => 'PUJ',
        ]);

        $this->actingAs($admin)
            ->from(route('users.gurus.create'))
            ->post(route('users.gurus.store'), [
                'name' => 'Guru Baru',
                'email' => 'guru-baru@example.test',
                'pasti_id' => $pasti->id,
                'is_assistant' => 0,
                'active' => 1,
            ])
            ->assertSessionHasErrors('kad_pengenalan');
    }

    public function test_admin_cannot_store_assistant_without_identity_card(): void
    {
        Role::findOrCreate('master_admin');

        $admin = User::factory()->create();
        $admin->assignRole('master_admin');

        $kawasan = Kawasan::query()->create([
            'name' => 'Kawasan Ujian',
            'code' => 'KUJ',
        ]);

        $pasti = Pasti::query()->create([
            'kawasan_id' => $kawasan->id,
            'name' => 'PASTI Ujian',
            'code' => 'PUJ',
        ]);

        $this->actingAs($admin)
            ->from(route('users.gurus.create'))
            ->post(route('users.gurus.store'), [
                'name' => 'Pembantu Baru',
                'email' => 'pembantu-baru@example.test',
                'pasti_id' => $pasti->id,
                'is_assistant' => 1,
                'active' => 1,
                'avatar' => UploadedFile::fake()->create('pembantu.jpg', 100, 'image/jpeg'),
            ])
            ->assertSessionHasErrors('kad_pengenalan');
    }

    public function test_admin_cannot_store_assistant_from_assistant_page_without_identity_card(): void
    {
        Role::findOrCreate('master_admin');

        $admin = User::factory()->create();
        $admin->assignRole('master_admin');

        $kawasan = Kawasan::query()->create([
            'name' => 'Kawasan Ujian',
            'code' => 'KUJ',
        ]);

        $pasti = Pasti::query()->create([
            'kawasan_id' => $kawasan->id,
            'name' => 'PASTI Ujian',
            'code' => 'PUJ',
        ]);

        $guruUtama = Guru::query()->create([
            'pasti_id' => $pasti->id,
            'name' => 'Guru Utama',
            'email' => 'guru-utama@example.test',
            'kad_pengenalan' => '900101-01-1111',
            'is_assistant' => false,
            'active' => true,
        ]);

        $this->actingAs($admin)
            ->from(route('users.gurus.assistants', ['users_guru' => $guruUtama, 'tab' => 'add']))
            ->post(route('users.gurus.assistants.store', $guruUtama), [
                'name' => 'Pembantu Halaman Khas',
                'email' => 'pembantu-sendiri@example.test',
                'active' => 1,
                'avatar' => UploadedFile::fake()->create('pembantu-sendiri.jpg', 100, 'image/jpeg'),
            ])
            ->assertSessionHasErrors('kad_pengenalan');
    }

    public function test_admin_can_store_assistant_with_allowance_fields(): void
    {
        Role::findOrCreate('master_admin');

        $admin = User::factory()->create();
        $admin->assignRole('master_admin');

        $kawasan = Kawasan::query()->create([
            'name' => 'Kawasan Ujian',
            'code' => 'KUJ',
        ]);

        $pasti = Pasti::query()->create([
            'kawasan_id' => $kawasan->id,
            'name' => 'PASTI Ujian',
            'code' => 'PUJ',
        ]);

        $this->actingAs($admin)
            ->post(route('users.gurus.store'), [
                'name' => 'Pembantu Berelaun',
                'email' => 'pembantu-elaun@example.test',
                'pasti_id' => $pasti->id,
                'is_assistant' => 1,
                'kad_pengenalan' => '900101-01-1234',
                'elaun' => 150,
                'elaun_transit' => 40,
                'elaun_lain' => 25,
                'active' => 1,
                'avatar' => UploadedFile::fake()->create('pembantu-elaun.jpg', 100, 'image/jpeg'),
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('gurus', [
            'name' => 'Pembantu Berelaun',
            'is_assistant' => true,
            'elaun' => '150.00',
            'elaun_transit' => '40.00',
            'elaun_lain' => '25.00',
        ]);
    }

    public function test_admin_guru_form_shows_allowance_fields_only_for_assistant_mode(): void
    {
        Role::findOrCreate('master_admin');

        $admin = User::factory()->create();
        $admin->assignRole('master_admin');

        $this->actingAs($admin)
            ->get(route('users.gurus.create'))
            ->assertOk()
            ->assertSee('name="elaun"', false)
            ->assertSee('name="elaun_transit"', false)
            ->assertSee('name="elaun_lain"', false)
            ->assertSee('x-show="isAssistant === 1"', false);
    }
}
