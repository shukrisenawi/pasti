<?php

namespace Database\Seeders;

use App\Models\Guru;
use App\Models\User;
use App\Support\GuruProfileCompletionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class GuruBulkSeeder extends Seeder
{
    public function run(): void
    {
        Role::findOrCreate('guru');

        $gurus = [
            ['name' => 'Fatimah binti mohamad', 'email' => 'fatimah3015@gmail.com'],
            ['name' => 'Nur Fatin Izaty Binti Mohd Khair Johari', 'email' => 'joharifatin59@gmail.com'],
            ['name' => 'Nor Fatin Binti Mohd Yusoff', 'email' => 'fatinyusoff0@gmail.com'],
            ['name' => 'Noraini binti Abd Rahman', 'email' => 'norainiabdrahman83@gmail.com'],
            ['name' => 'Rosmimi Bt Rusdi', 'email' => 'rosmimirusdi28@gmail.com'],
            ['name' => 'Mahirah bt dzakaria', 'email' => 'mahirahdzakaria@gmail.com'],
            ['name' => 'Asma Hani Binti Abdul Rani', 'email' => 'aasmahaniabdulrani@gmail.com'],
            ['name' => 'Noor Ummi binti Umar', 'email' => 'noorummiumar@gmail.com'],
            ['name' => 'Siti Munira binti Bidin', 'email' => 's.munira6048@gmail.com'],
            ['name' => 'Noor Nadia Bt Mohamed', 'email' => 'nadia85mohamed@gmail.com'],
            ['name' => 'Nor Atikah Binti Ibrahim', 'email' => 'noratikahibrahim96@gmail.com'],
            ['name' => 'Rosnani Binti Zali', 'email' => 'rosnanizali25@gmail.com'],
            ['name' => 'Halimahton saadiah bt hussain', 'email' => 'imahsyifaimahsyifa@gmail.com'],
            ['name' => 'Siti Khadijah Binti Afifi', 'email' => 'sitiafifi96@gmail.com'],
            ['name' => 'Adilah bt Abd Samad', 'email' => 'adilahsumayyah@gmail.com'],
            ['name' => 'Nurul Ain Binti Abdul Samat', 'email' => 'nurulain23.10.89@gmail.com'],
            ['name' => 'Suriani Binti Noordin', 'email' => 'surianiinoordin@gmail.com'],
            ['name' => 'Fauziah Binti Shuhaimi', 'email' => 'fauziahshuhaimi03@gmail.com'],
            ['name' => 'Roshaliza Bt Awang', 'email' => 'roshalizaawang@gmail.com'],
            ['name' => 'Nazurah Bt Ismail', 'email' => 'nzura3337@gmail.com'],
            ['name' => 'Noor Hafizah BT Baharom', 'email' => 'fizah68@gmail.com'],
            ['name' => 'SITI RASHIDAH BINTI MD IDRUS', 'email' => 'firash2704@gmail.com'],
            ['name' => 'Siti Zainab binti Ghazali', 'email' => 'zainabghazali23@gmail.com'],
            ['name' => 'Mariani Binti Ab Rashid', 'email' => 'rosmariayanie@gmail.com'],
            ['name' => 'Nuradilah Farhanabinti Harum', 'email' => 'farhanaharun009@gmail.com'],
            ['name' => 'Nooraini bint tvi Haya', 'email' => 'ainihaya71@gmail.com'],
            ['name' => 'Jareah bt ahmad', 'email' => 'jariahahmad89@gmail.com'],
            ['name' => "A'fifah bt mohamed", 'email' => 'afifahjasmee@gmail.com'],
            ['name' => 'Siti Najwa Binti Sulaiman', 'email' => 'snnajwa15@gmail.com'],
            ['name' => 'Nor Khuzamah binti Ahmad', 'email' => 'khuzaimah581@gmail.com'],
            ['name' => 'SITI NOOR BT ZAKARIA.', 'email' => 'sitinoorzakaria36@gmail.com'],
            ['name' => 'Mashitah Binti Ayub', 'email' => 'mashitahmasyie950820@gmail.com'],
            ['name' => 'Sarina Binti Ahmad', 'email' => 'sarinaahmad508@gmail.com'],
            ['name' => 'Fatin Zubaidah binti Ghazali', 'email' => 'fatin96ghazali@gmail.com'],
            ['name' => 'Nafizah binti Dollah', 'email' => 'nafizahdollah61@gmail.com'],
            ['name' => 'Roslina Lahman', 'email' => 'linalahman78@gmail.com'],
            ['name' => 'Nur Famiza Fazilah', 'email' => 'nazamiza8389@gmail.com'],
        ];

        foreach ($gurus as $guruData) {
            $name = trim($guruData['name']);
            $email = strtolower(trim($guruData['email']));

            $user = User::query()->firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'nama_samaran' => $name,
                    'password' => Hash::make(GuruProfileCompletionService::DEFAULT_GURU_PASSWORD),
                    'email_verified_at' => now(),
                    'locale' => 'ms',
                    'force_password_change' => true,
                ]
            );

            $user->forceFill([
                'name' => $name,
                'nama_samaran' => $name,
                'locale' => 'ms',
                'force_password_change' => true,
            ])->save();

            if (! $user->hasRole('guru')) {
                $user->assignRole('guru');
            }

            Guru::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'pasti_id' => null,
                    'name' => $name,
                    'email' => $email,
                    'is_assistant' => false,
                    'phone' => null,
                    'joined_at' => null,
                    'active' => true,
                    'avatar_path' => null,
                    'marital_status' => null,
                    'kursus_guru' => null,
                    'terima_anugerah' => false,
                ]
            );
        }
    }
}

