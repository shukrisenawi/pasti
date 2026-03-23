<?php

namespace Database\Seeders;

use App\Models\AjkPosition;
use Illuminate\Database\Seeder;

class AjkPositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $positions = [
            [
                'name' => 'Pengarah Program',
                'description' => 'Bertanggungjawab menyelaras keseluruhan perancangan dan pelaksanaan program.',
            ],
            [
                'name' => 'Setiausaha',
                'description' => 'Mengurus surat-menyurat, dokumentasi dan laporan program.',
            ],
            [
                'name' => 'Bendahari',
                'description' => 'Mengurus kewangan termasuk bajet, kutipan dan perbelanjaan.',
            ],
            [
                'name' => 'AJK Protokol',
                'description' => 'Mengurus susunan kehadiran tetamu dan memastikan perjalanan majlis mengikut etika.',
            ],
            [
                'name' => 'AJK Aturcara Majlis',
                'description' => 'Menyusun dan memantau perjalanan aturcara program.',
            ],
            [
                'name' => 'Pengacara Majlis',
                'description' => 'Mengendalikan majlis dan memastikan program berjalan lancar mengikut aturcara.',
            ],
            [
                'name' => 'AJK Pendaftaran',
                'description' => 'Mengurus kehadiran peserta dan rekod pendaftaran.',
            ],
            [
                'name' => 'AJK Jamuan',
                'description' => 'Menyediakan dan mengurus makanan serta minuman.',
            ],
            [
                'name' => 'AJK Kebersihan',
                'description' => 'Memastikan kebersihan kawasan sebelum, semasa dan selepas program.',
            ],
            [
                'name' => 'AJK Logistik',
                'description' => 'Mengurus penyediaan, susun atur dan peralatan program.',
            ],
            [
                'name' => 'AJK Dokumentasi',
                'description' => 'Mengambil gambar, video dan menyediakan rekod program.',
            ],
            [
                'name' => 'AJK Publisiti',
                'description' => 'Menghebahkan program melalui pelbagai saluran.',
            ],
            [
                'name' => 'AJK Kawalan Murid',
                'description' => 'Mengawal disiplin dan pergerakan murid sepanjang program.',
            ],
            [
                'name' => 'AJK Hadiah & Sijil',
                'description' => 'Menyediakan dan mengurus penyampaian hadiah serta sijil.',
            ],
        ];

        foreach ($positions as $position) {
            AjkPosition::query()->updateOrCreate(
                ['name' => $position['name']],
                ['description' => $position['description']]
            );
        }
    }
}

