<?php

namespace Tests\Feature;

use App\Models\Kawasan;
use App\Models\Pasti;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
