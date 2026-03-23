<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\ProgramStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $roles = ['master_admin', 'admin', 'guru'];
        foreach ($roles as $role) {
            Role::findOrCreate($role);
        }

        $masterAdmin = User::query()->firstOrCreate(
            ['email' => 'master@pasti'],
            [
                'name' => 'Master Admin',
                'nama_samaran' => 'Master Admin',
                'password' => Hash::make('123'),
                'email_verified_at' => now(),
                'locale' => 'ms',
            ]
        );

        $masterAdmin->syncRoles(['master_admin']);

        ProgramStatus::query()->firstOrCreate(
            ['code' => 'HADIR'],
            ['name' => 'Hadir', 'is_hadir' => true]
        );
        ProgramStatus::query()->firstOrCreate(
            ['code' => 'TIDAK_HADIR'],
            ['name' => 'Tidak Hadir', 'is_hadir' => false]
        );

        ProgramStatus::query()
            ->whereNotIn('code', ['HADIR', 'TIDAK_HADIR'])
            ->delete();

        $this->call([
            AjkPositionSeeder::class,
        ]);
    }
}
