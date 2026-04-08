<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use App\Services\N8nWebhookService;
use Illuminate\Database\Seeder;

class N8nSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            N8nWebhookService::KEY_WEBHOOK_MODE => 'production',
            N8nWebhookService::KEY_WEBHOOK_GROUP => 'real',
            N8nWebhookService::KEY_WEBHOOK_URL_TEST => 'https://n8n-mt8umikivytz.n8x.biz.id/webhook-test/3caf9b20-d664-491b-81db-57984d626341',
            N8nWebhookService::KEY_WEBHOOK_URL_PRODUCTION => 'https://n8n-mt8umikivytz.n8x.biz.id/webhook/3caf9b20-d664-491b-81db-57984d626341',
            N8nWebhookService::KEY_WEBHOOK_URL_GROUP2_TEST => 'https://n8n-mt8umikivytz.n8x.biz.id/webhook-test/3caf9b20-d664-491b-81db-57984d626341',
            N8nWebhookService::KEY_WEBHOOK_URL_GROUP2_PRODUCTION => 'https://n8n-mt8umikivytz.n8x.biz.id/webhook-test/3caf9b20-d664-491b-81db-57984d626341',

            N8nWebhookService::KEY_TEXT_PROGRAM_CREATED => '{tajuk} akan diadakan pada {tarikh} ({hari}){masa}{lokasi}.',
            N8nWebhookService::KEY_TEXT_SALARY_REQUEST => 'Permintaan kemaskini gaji guru telah dihantar pada {tarikh}. Sila kemaskini gaji dan elaun semasa.',
            N8nWebhookService::KEY_TEXT_PASTI_INFO_REQUEST => 'Permintaan kemaskini maklumat PASTI telah dihantar pada {tarikh}. Sila lengkapkan maklumat terkini PASTI.',
            N8nWebhookService::KEY_TEXT_GURU_COURSE_OFFER => 'Permintaan sambung Kursus Guru ke Semester {semester} telah dihantar. Tarikh akhir pendaftaran: {tarikh_akhir}.{nota}',
            N8nWebhookService::KEY_TEXT_LEAVE_NOTICE_SUBMITTED => '{nama_guru} hantar notis cuti pada {tarikh_cuti} hingga {tarikh_hingga}. Sebab: {sebab}.',
            N8nWebhookService::KEY_TEXT_CLAIM_SUBMITTED => '{nama_guru} hantar claim sebanyak RM{jumlah} pada {tarikh_claim}. Catatan: {catatan}.',
            N8nWebhookService::KEY_TEXT_ALL_PASTI_INFO_COMPLETED => 'Semua PASTI telah hantar maklumat PASTI. Dikemaskini pada {tarikh}.',
            N8nWebhookService::KEY_TEXT_ALL_GURU_SALARY_COMPLETED => 'Semua guru telah hantar maklumat gaji semasa. Dikemaskini pada {tarikh}.',
        ];

        foreach ($settings as $key => $value) {
            SystemSetting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }
    }
}
