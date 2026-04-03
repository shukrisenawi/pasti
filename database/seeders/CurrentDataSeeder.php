<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CurrentDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        DB::table('program_title_options')->truncate();
        DB::table('program_statuses')->truncate();
        DB::table('pemarkahan_title_options')->truncate();
        DB::table('guru_salary_requests')->truncate();
        DB::table('financial_transaction_types')->truncate();
        DB::table('ajk_positions')->truncate();
        DB::table('pastis')->truncate();
        DB::table('kawasans')->truncate();
        DB::table('model_has_roles')->truncate();
        DB::table('users')->truncate();
        DB::table('roles')->truncate();

        DB::table('roles')->insert(
        array (
          0 => 
          array (
            'id' => 1,
            'name' => 'master_admin',
            'guard_name' => 'web',
            'created_at' => '2026-04-03 16:46:39',
            'updated_at' => '2026-04-03 16:46:39',
          ),
          1 => 
          array (
            'id' => 2,
            'name' => 'admin',
            'guard_name' => 'web',
            'created_at' => '2026-04-03 16:46:39',
            'updated_at' => '2026-04-03 16:46:39',
          ),
          2 => 
          array (
            'id' => 3,
            'name' => 'guru',
            'guard_name' => 'web',
            'created_at' => '2026-04-03 16:46:39',
            'updated_at' => '2026-04-03 16:46:39',
          ),
        )
        );

        DB::table('users')->insert(
        array (
          0 => 
          array (
            'id' => 1,
            'name' => 'Master Admin',
            'nama_samaran' => 'Master Admin',
            'tarikh_lahir' => '1985-12-03',
            'tarikh_exp_skim_pas' => NULL,
            'email' => 'master@pasti',
            'locale' => 'ms',
            'admin_assignment_scope' => NULL,
            'avatar_path' => 'avatars/9ulV58rog3F5IggNAD86znFZhPz2AC7WFXlHh6xu.png',
            'email_verified_at' => '2026-04-03 16:46:39',
            'password' => '$2y$12$85nUC8bqIMB.cUcjWzJLBu.8FcjELmjg4JjsyEaK8ofueZ/.USrpm',
            'force_password_change' => 0,
            'remember_token' => NULL,
            'created_at' => '2026-04-03 16:46:39',
            'updated_at' => '2026-04-03 16:47:20',
          ),
        )
        );

        DB::table('model_has_roles')->insert(
        array (
          0 => 
          array (
            'role_id' => 1,
            'model_type' => 'App\\Models\\User',
            'model_id' => 1,
          ),
        )
        );

        DB::table('kawasans')->insert(
        array (
          0 => 
          array (
            'id' => 1,
            'name' => 'JENERI',
            'dun' => 'JENERI',
            'code' => NULL,
            'created_at' => '2026-04-03 16:48:02',
            'updated_at' => '2026-04-03 16:48:02',
          ),
          1 => 
          array (
            'id' => 2,
            'name' => 'BELANTEK',
            'dun' => 'BELANTEK',
            'code' => NULL,
            'created_at' => '2026-04-03 16:48:12',
            'updated_at' => '2026-04-03 16:48:12',
          ),
        )
        );

        DB::table('pastis')->insert(
        array (
          0 => 
          array (
            'id' => 1,
            'kawasan_id' => 2,
            'name' => 'AL-ABROR',
            'code' => 'K100111',
            'address' => NULL,
            'phone' => NULL,
            'manager_name' => NULL,
            'manager_phone' => NULL,
            'image_path' => NULL,
            'created_at' => '2026-04-03 16:48:02',
            'updated_at' => '2026-04-03 16:48:12',
          ),
          1 => 
          array (
            'id' => 2,
            'kawasan_id' => 1,
            'name' => 'AL-AZFAR',
            'code' => 'K100202',
            'address' => NULL,
            'phone' => NULL,
            'manager_name' => NULL,
            'manager_phone' => NULL,
            'image_path' => NULL,
            'created_at' => '2026-04-03 16:48:34',
            'updated_at' => '2026-04-03 16:48:34',
          ),
          2 => 
          array (
            'id' => 3,
            'kawasan_id' => 2,
            'name' => 'AL-BADRU',
            'code' => 'K100102',
            'address' => NULL,
            'phone' => NULL,
            'manager_name' => NULL,
            'manager_phone' => NULL,
            'image_path' => NULL,
            'created_at' => '2026-04-03 16:48:51',
            'updated_at' => '2026-04-03 16:49:03',
          ),
          3 => 
          array (
            'id' => 4,
            'kawasan_id' => 2,
            'name' => 'AL-EHSAN',
            'code' => 'K100103',
            'address' => NULL,
            'phone' => NULL,
            'manager_name' => NULL,
            'manager_phone' => NULL,
            'image_path' => NULL,
            'created_at' => '2026-04-03 16:49:23',
            'updated_at' => '2026-04-03 16:49:23',
          ),
          4 => 
          array (
            'id' => 5,
            'kawasan_id' => 2,
            'name' => 'AL-FALAH',
            'code' => 'K100104',
            'address' => NULL,
            'phone' => NULL,
            'manager_name' => NULL,
            'manager_phone' => NULL,
            'image_path' => NULL,
            'created_at' => '2026-04-03 16:49:46',
            'updated_at' => '2026-04-03 16:49:46',
          ),
          5 => 
          array (
            'id' => 6,
            'kawasan_id' => 1,
            'name' => 'AL-FAWWAZ',
            'code' => 'K100203',
            'address' => NULL,
            'phone' => NULL,
            'manager_name' => NULL,
            'manager_phone' => NULL,
            'image_path' => NULL,
            'created_at' => '2026-04-03 16:50:03',
            'updated_at' => '2026-04-03 16:50:03',
          ),
          6 => 
          array (
            'id' => 7,
            'kawasan_id' => 2,
            'name' => 'AL-FURQAN',
            'code' => 'K100105',
            'address' => NULL,
            'phone' => NULL,
            'manager_name' => NULL,
            'manager_phone' => NULL,
            'image_path' => NULL,
            'created_at' => '2026-04-03 16:50:27',
            'updated_at' => '2026-04-03 16:50:27',
          ),
          7 => 
          array (
            'id' => 8,
            'kawasan_id' => 1,
            'name' => 'AL-HUSNA',
            'code' => 'K100204',
            'address' => NULL,
            'phone' => NULL,
            'manager_name' => NULL,
            'manager_phone' => NULL,
            'image_path' => NULL,
            'created_at' => '2026-04-03 16:50:49',
            'updated_at' => '2026-04-03 16:50:49',
          ),
          8 => 
          array (
            'id' => 9,
            'kawasan_id' => 1,
            'name' => 'AL-MISBAH',
            'code' => 'K100205',
            'address' => NULL,
            'phone' => NULL,
            'manager_name' => NULL,
            'manager_phone' => NULL,
            'image_path' => NULL,
            'created_at' => '2026-04-03 16:51:06',
            'updated_at' => '2026-04-03 16:51:06',
          ),
          9 => 
          array (
            'id' => 10,
            'kawasan_id' => 2,
            'name' => 'AN-NAJAAH',
            'code' => 'K100107',
            'address' => NULL,
            'phone' => NULL,
            'manager_name' => NULL,
            'manager_phone' => NULL,
            'image_path' => NULL,
            'created_at' => '2026-04-03 16:51:26',
            'updated_at' => '2026-04-03 16:51:26',
          ),
          10 => 
          array (
            'id' => 11,
            'kawasan_id' => 2,
            'name' => 'AN-NASYEIN',
            'code' => 'K100108',
            'address' => NULL,
            'phone' => NULL,
            'manager_name' => NULL,
            'manager_phone' => NULL,
            'image_path' => NULL,
            'created_at' => '2026-04-03 16:51:43',
            'updated_at' => '2026-04-03 16:51:43',
          ),
          11 => 
          array (
            'id' => 12,
            'kawasan_id' => 1,
            'name' => 'BADRUL HUDA',
            'code' => 'K100211',
            'address' => NULL,
            'phone' => NULL,
            'manager_name' => NULL,
            'manager_phone' => NULL,
            'image_path' => NULL,
            'created_at' => '2026-04-03 16:52:02',
            'updated_at' => '2026-04-03 16:52:02',
          ),
          12 => 
          array (
            'id' => 13,
            'kawasan_id' => 1,
            'name' => 'IBNU ABBAS',
            'code' => 'K100207',
            'address' => NULL,
            'phone' => NULL,
            'manager_name' => NULL,
            'manager_phone' => NULL,
            'image_path' => NULL,
            'created_at' => '2026-04-03 16:52:34',
            'updated_at' => '2026-04-03 16:52:34',
          ),
          13 => 
          array (
            'id' => 14,
            'kawasan_id' => 1,
            'name' => 'RAUDHATUL JANNAH',
            'code' => 'K100209',
            'address' => NULL,
            'phone' => NULL,
            'manager_name' => NULL,
            'manager_phone' => NULL,
            'image_path' => NULL,
            'created_at' => '2026-04-03 16:52:51',
            'updated_at' => '2026-04-03 16:52:51',
          ),
        )
        );

        DB::table('ajk_positions')->insert(
        array (
          0 => 
          array (
            'id' => 1,
            'name' => 'Pengarah Program',
            'description' => 'Bertanggungjawab menyelaras keseluruhan perancangan dan pelaksanaan program.',
            'created_at' => '2026-04-03 16:46:39',
            'updated_at' => '2026-04-03 16:46:39',
          ),
          1 => 
          array (
            'id' => 2,
            'name' => 'Setiausaha',
            'description' => 'Mengurus surat-menyurat, dokumentasi dan laporan program.',
            'created_at' => '2026-04-03 16:46:39',
            'updated_at' => '2026-04-03 16:46:39',
          ),
          2 => 
          array (
            'id' => 3,
            'name' => 'Bendahari',
            'description' => 'Mengurus kewangan termasuk bajet, kutipan dan perbelanjaan.',
            'created_at' => '2026-04-03 16:46:39',
            'updated_at' => '2026-04-03 16:46:39',
          ),
          3 => 
          array (
            'id' => 4,
            'name' => 'AJK Protokol',
            'description' => 'Mengurus susunan kehadiran tetamu dan memastikan perjalanan majlis mengikut etika.',
            'created_at' => '2026-04-03 16:46:39',
            'updated_at' => '2026-04-03 16:46:39',
          ),
          4 => 
          array (
            'id' => 5,
            'name' => 'AJK Aturcara Majlis',
            'description' => 'Menyusun dan memantau perjalanan aturcara program.',
            'created_at' => '2026-04-03 16:46:39',
            'updated_at' => '2026-04-03 16:46:39',
          ),
          5 => 
          array (
            'id' => 6,
            'name' => 'Pengacara Majlis',
            'description' => 'Mengendalikan majlis dan memastikan program berjalan lancar mengikut aturcara.',
            'created_at' => '2026-04-03 16:46:39',
            'updated_at' => '2026-04-03 16:46:39',
          ),
          6 => 
          array (
            'id' => 7,
            'name' => 'AJK Pendaftaran',
            'description' => 'Mengurus kehadiran peserta dan rekod pendaftaran.',
            'created_at' => '2026-04-03 16:46:39',
            'updated_at' => '2026-04-03 16:46:39',
          ),
          7 => 
          array (
            'id' => 8,
            'name' => 'AJK Jamuan',
            'description' => 'Menyediakan dan mengurus makanan serta minuman.',
            'created_at' => '2026-04-03 16:46:39',
            'updated_at' => '2026-04-03 16:46:39',
          ),
          8 => 
          array (
            'id' => 9,
            'name' => 'AJK Kebersihan',
            'description' => 'Memastikan kebersihan kawasan sebelum, semasa dan selepas program.',
            'created_at' => '2026-04-03 16:46:39',
            'updated_at' => '2026-04-03 16:46:39',
          ),
          9 => 
          array (
            'id' => 10,
            'name' => 'AJK Logistik',
            'description' => 'Mengurus penyediaan, susun atur dan peralatan program.',
            'created_at' => '2026-04-03 16:46:39',
            'updated_at' => '2026-04-03 16:46:39',
          ),
          10 => 
          array (
            'id' => 11,
            'name' => 'AJK Dokumentasi',
            'description' => 'Mengambil gambar, video dan menyediakan rekod program.',
            'created_at' => '2026-04-03 16:46:39',
            'updated_at' => '2026-04-03 16:46:39',
          ),
          11 => 
          array (
            'id' => 12,
            'name' => 'AJK Publisiti',
            'description' => 'Menghebahkan program melalui pelbagai saluran.',
            'created_at' => '2026-04-03 16:46:39',
            'updated_at' => '2026-04-03 16:46:39',
          ),
          12 => 
          array (
            'id' => 13,
            'name' => 'AJK Kawalan Murid',
            'description' => 'Mengawal disiplin dan pergerakan murid sepanjang program.',
            'created_at' => '2026-04-03 16:46:39',
            'updated_at' => '2026-04-03 16:46:39',
          ),
          13 => 
          array (
            'id' => 14,
            'name' => 'AJK Hadiah & Sijil',
            'description' => 'Menyediakan dan mengurus penyampaian hadiah serta sijil.',
            'created_at' => '2026-04-03 16:46:39',
            'updated_at' => '2026-04-03 16:46:39',
          ),
        )
        );

        DB::table('financial_transaction_types')->insert(
        array (
          0 => 
          array (
            'id' => 1,
            'name' => 'Lain-lain',
            'is_active' => 1,
            'created_by' => NULL,
            'created_at' => '2026-04-03 16:46:38',
            'updated_at' => '2026-04-03 16:46:38',
          ),
          1 => 
          array (
            'id' => 2,
            'name' => 'Claim',
            'is_active' => 1,
            'created_by' => NULL,
            'created_at' => '2026-04-03 16:46:39',
            'updated_at' => '2026-04-03 16:46:39',
          ),
        )
        );

        DB::table('pemarkahan_title_options')->insert(
        array (
          0 => 
          array (
            'id' => 1,
            'title' => 'Penaziran Infrastruktur',
            'sort_order' => 1,
            'is_active' => 1,
            'created_by' => NULL,
            'created_at' => '2026-04-03 16:46:38',
            'updated_at' => '2026-04-03 16:46:38',
          ),
          1 => 
          array (
            'id' => 2,
            'title' => 'Penaziran Pengajaran Guru',
            'sort_order' => 2,
            'is_active' => 1,
            'created_by' => NULL,
            'created_at' => '2026-04-03 16:46:38',
            'updated_at' => '2026-04-03 16:46:38',
          ),
        )
        );

        DB::table('program_statuses')->insert(
        array (
          0 => 
          array (
            'id' => 1,
            'name' => 'Hadir',
            'code' => 'HADIR',
            'is_hadir' => 1,
            'created_at' => '2026-04-03 16:46:39',
            'updated_at' => '2026-04-03 16:46:39',
          ),
          1 => 
          array (
            'id' => 2,
            'name' => 'Tidak Hadir',
            'code' => 'TIDAK_HADIR',
            'is_hadir' => 0,
            'created_at' => '2026-04-03 16:46:39',
            'updated_at' => '2026-04-03 16:46:39',
          ),
        )
        );

        DB::table('program_title_options')->insert(
        array (
          0 => 
          array (
            'id' => 1,
            'title' => 'Sukan PASTI',
            'markah' => 1,
            'sort_order' => 1,
            'is_active' => 1,
            'created_by' => NULL,
            'created_at' => '2026-04-03 16:46:38',
            'updated_at' => '2026-04-03 16:46:38',
          ),
          1 => 
          array (
            'id' => 2,
            'title' => 'Usrah',
            'markah' => 1,
            'sort_order' => 2,
            'is_active' => 1,
            'created_by' => NULL,
            'created_at' => '2026-04-03 16:46:38',
            'updated_at' => '2026-04-03 16:46:38',
          ),
          2 => 
          array (
            'id' => 3,
            'title' => 'Hari Anugerah Prestasi PASTI',
            'markah' => 1,
            'sort_order' => 3,
            'is_active' => 1,
            'created_by' => NULL,
            'created_at' => '2026-04-03 16:46:38',
            'updated_at' => '2026-04-03 16:46:38',
          ),
        )
        );

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}

