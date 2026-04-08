<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use App\Services\N8nWebhookService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class N8nSettingController extends Controller
{
    public function edit(Request $request, N8nWebhookService $n8nWebhookService): View
    {
        abort_unless($request->user()->hasRole('master_admin'), 403);

        return view('n8n-settings.edit', [
            'settings' => $n8nWebhookService->allSettings(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasRole('master_admin'), 403);

        if (! Schema::hasTable('system_settings')) {
            return redirect()
                ->route('n8n-settings.edit')
                ->withErrors([
                    'n8n_settings' => 'Jadual tetapan belum wujud. Sila jalankan migrate terlebih dahulu.',
                ]);
        }

        $validated = $request->validate([
            'webhook_mode' => ['required', 'in:test,production'],
            'webhook_group' => ['required', 'in:test,real'],
            'webhook_url_test' => ['required', 'url', 'max:2000'],
            'webhook_url_production' => ['required', 'url', 'max:2000'],
            'webhook_url_group2_test' => ['required', 'url', 'max:2000'],
            'webhook_url_group2_production' => ['required', 'url', 'max:2000'],
            'text_program_created' => ['required', 'string', 'max:2000'],
            'text_salary_request' => ['required', 'string', 'max:2000'],
            'text_pasti_info_request' => ['required', 'string', 'max:2000'],
            'text_guru_course_offer' => ['required', 'string', 'max:2000'],
            'text_leave_notice_submitted' => ['required', 'string', 'max:2000'],
            'text_claim_submitted' => ['required', 'string', 'max:2000'],
            'text_all_pasti_info_completed' => ['required', 'string', 'max:2000'],
            'text_all_guru_salary_completed' => ['required', 'string', 'max:2000'],
        ]);

        $mapping = [
            'webhook_mode' => N8nWebhookService::KEY_WEBHOOK_MODE,
            'webhook_group' => N8nWebhookService::KEY_WEBHOOK_GROUP,
            'webhook_url_test' => N8nWebhookService::KEY_WEBHOOK_URL_TEST,
            'webhook_url_production' => N8nWebhookService::KEY_WEBHOOK_URL_PRODUCTION,
            'webhook_url_group2_test' => N8nWebhookService::KEY_WEBHOOK_URL_GROUP2_TEST,
            'webhook_url_group2_production' => N8nWebhookService::KEY_WEBHOOK_URL_GROUP2_PRODUCTION,
            'text_program_created' => 'n8n_text_program_created',
            'text_salary_request' => 'n8n_text_salary_request',
            'text_pasti_info_request' => 'n8n_text_pasti_info_request',
            'text_guru_course_offer' => 'n8n_text_guru_course_offer',
            'text_leave_notice_submitted' => 'n8n_text_leave_notice_submitted',
            'text_claim_submitted' => 'n8n_text_claim_submitted',
            'text_all_pasti_info_completed' => N8nWebhookService::KEY_TEXT_ALL_PASTI_INFO_COMPLETED,
            'text_all_guru_salary_completed' => N8nWebhookService::KEY_TEXT_ALL_GURU_SALARY_COMPLETED,
        ];

        foreach ($mapping as $inputKey => $settingKey) {
            SystemSetting::query()->updateOrCreate(
                ['key' => $settingKey],
                ['value' => trim((string) $validated[$inputKey])]
            );
        }

        return redirect()
            ->route('n8n-settings.edit')
            ->with('status', __('messages.saved'));
    }
}
