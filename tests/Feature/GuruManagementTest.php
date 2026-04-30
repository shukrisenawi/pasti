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

    public function test_admin_cannot_store_assistant_without_phone(): void
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
                'name' => 'Pembantu Tanpa Telefon',
                'pasti_id' => $pasti->id,
                'is_assistant' => 1,
                'kad_pengenalan' => '900101-01-1234',
                'avatar' => UploadedFile::fake()->create('pembantu-tanpa-telefon.jpg', 100, 'image/jpeg'),
            ])
            ->assertSessionHasErrors('phone');
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
                'phone' => '0123456789',
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
            'phone' => '0123456789',
            'elaun' => '150.00',
            'elaun_transit' => '40.00',
            'elaun_lain' => '25.00',
        ]);
    }

    public function test_assistant_forms_only_show_required_assistant_fields(): void
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

        $assistant = Guru::query()->create([
            'pasti_id' => $pasti->id,
            'name' => 'Pembantu Sedia Ada',
            'kad_pengenalan' => '900101-01-2222',
            'elaun' => 120,
            'elaun_transit' => 30,
            'elaun_lain' => 15,
            'is_assistant' => true,
            'active' => true,
        ]);

        $assistantPage = $this->actingAs($admin)
            ->get(route('users.gurus.assistants', ['users_guru' => Guru::query()->create([
                'pasti_id' => $pasti->id,
                'name' => 'Guru Utama',
                'kad_pengenalan' => '900101-01-1111',
                'is_assistant' => false,
                'active' => true,
            ]), 'tab' => 'add']))
            ->assertOk();

        $assistantPage
            ->assertSee('name="name"', false)
            ->assertSee('name="phone"', false)
            ->assertSee('name="kad_pengenalan"', false)
            ->assertSee('name="elaun"', false)
            ->assertSee('name="elaun_transit"', false)
            ->assertSee('name="elaun_lain"', false)
            ->assertSee('name="avatar"', false)
            ->assertDontSee('name="email"', false)
            ->assertDontSee('name="joined_at"', false)
            ->assertDontSee('name="active"', false);

        $this->actingAs($admin)
            ->get(route('users.gurus.edit', $assistant))
            ->assertOk()
            ->assertSee('name="name"', false)
            ->assertSee('name="pasti_id"', false)
            ->assertSee('name="phone"', false)
            ->assertSee('name="kad_pengenalan"', false)
            ->assertSee('name="elaun"', false)
            ->assertSee('name="elaun_transit"', false)
            ->assertSee('name="elaun_lain"', false)
            ->assertSee('name="avatar"', false)
            ->assertDontSee('name="email"', false)
            ->assertDontSee('name="joined_at"', false)
            ->assertDontSee('name="active"', false)
            ->assertDontSee('name="marital_status"', false)
            ->assertDontSee('name="kursus_guru"', false);
    }

    public function test_admin_guru_form_shows_allowance_fields_only_for_assistant_mode(): void
    {
        Role::findOrCreate('master_admin');

        $admin = User::factory()->create();
        $admin->assignRole('master_admin');

        $this->actingAs($admin)
            ->get(route('users.gurus.create'))
            ->assertOk()
            ->assertSee('name="phone"', false)
            ->assertSee(':required="isAssistant === 1"', false)
            ->assertSee('name="elaun"', false)
            ->assertSee('name="elaun_transit"', false)
            ->assertSee('name="elaun_lain"', false)
            ->assertSee('x-show="isAssistant === 1"', false);
    }
}
