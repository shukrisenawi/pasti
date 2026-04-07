<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class N8nWebhookService
{
    public const KEY_WEBHOOK_MODE = 'n8n_webhook_mode';

    public const KEY_WEBHOOK_URL_TEST = 'n8n_webhook_url_test';
    public const KEY_WEBHOOK_URL_PRODUCTION = 'n8n_webhook_url_production';
    public const KEY_WEBHOOK_URL_GROUP2_TEST = 'n8n_webhook_url_group2_test';
    public const KEY_WEBHOOK_URL_GROUP2_PRODUCTION = 'n8n_webhook_url_group2_production';

    public const KEY_TEXT_PROGRAM_CREATED = 'n8n_text_program_created';
    public const KEY_TEXT_SALARY_REQUEST = 'n8n_text_salary_request';
    public const KEY_TEXT_PASTI_INFO_REQUEST = 'n8n_text_pasti_info_request';
    public const KEY_TEXT_GURU_COURSE_OFFER = 'n8n_text_guru_course_offer';
    public const KEY_TEXT_LEAVE_NOTICE_SUBMITTED = 'n8n_text_leave_notice_submitted';
    public const KEY_TEXT_CLAIM_SUBMITTED = 'n8n_text_claim_submitted';

    private const DEFAULT_TEXT_PROGRAM_CREATED = '{tajuk} akan diadakan pada {tarikh} ({hari}){masa}{lokasi}.';
    private const DEFAULT_TEXT_SALARY_REQUEST = 'Permintaan kemaskini gaji guru telah dihantar pada {tarikh}. Sila kemaskini gaji dan elaun semasa.';
    private const DEFAULT_TEXT_PASTI_INFO_REQUEST = 'Permintaan kemaskini maklumat PASTI telah dihantar pada {tarikh}. Sila lengkapkan maklumat terkini PASTI.';
    private const DEFAULT_TEXT_GURU_COURSE_OFFER = 'Permintaan sambung Kursus Guru ke Semester {semester} telah dihantar. Tarikh akhir pendaftaran: {tarikh_akhir}.{nota}';
    private const DEFAULT_TEXT_LEAVE_NOTICE_SUBMITTED = '{nama_guru} hantar notis cuti pada {tarikh_cuti} hingga {tarikh_hingga}. Sebab: {sebab}.';
    private const DEFAULT_TEXT_CLAIM_SUBMITTED = '{nama_guru} hantar claim sebanyak RM{jumlah} pada {tarikh_claim}. Catatan: {catatan}.';

    public function send(string $text, ?string $link = null, ?string $gambar = null): void
    {
        $this->sendToWebhook($this->activeWebhookUrl(false), $text, $link, $gambar);
    }

    public function sendGroup2(string $text, ?string $link = null, ?string $gambar = null): void
    {
        $this->sendToWebhook($this->activeWebhookUrl(true), $text, $link, $gambar);
    }

    public function sendByTemplate(string $templateKey, array $variables, ?string $link = null, ?string $gambar = null): void
    {
        $template = $this->template($templateKey);
        $text = $this->renderTemplate($template, $variables);
        $this->send($text, $link, $gambar);
    }

    public function sendGroup2ByTemplate(string $templateKey, array $variables, ?string $link = null, ?string $gambar = null): void
    {
        $template = $this->template($templateKey);
        $text = $this->renderTemplate($template, $variables);
        $this->sendGroup2($text, $link, $gambar);
    }

    private function sendToWebhook(string $webhookUrl, string $text, ?string $link = null, ?string $gambar = null): void
    {
        if ($webhookUrl === '') {
            return;
        }

        $text = trim($text);
        $payload = [
            'text' => $text,
            'type' => $this->webhookMode(),
        ];

        if (filled($gambar)) {
            $payload['gambar'] = $gambar;
        }

        if (filled($link)) {
            $payload['link'] = $link;
        }

        try {
            $response = Http::post($webhookUrl, $payload);

            if ($response->failed()) {
                Log::warning('Webhook n8n pulang status gagal.', [
                    'status' => $response->status(),
                    'webhook_url' => $webhookUrl,
                    'payload' => $payload,
                    'response_body' => $response->body(),
                ]);
            }
        } catch (Throwable $e) {
            Log::warning('Gagal hantar webhook n8n.', [
                'message' => $e->getMessage(),
                'webhook_url' => $webhookUrl,
                'payload' => $payload,
            ]);
        }
    }

    public function toPublicUrl(?string $url): ?string
    {
        if (! filled($url)) {
            return null;
        }

        $publicDomain = rtrim((string) config('services.n8n.public_domain', 'https://pastikawasansik.my.id'), '/');

        if (str_starts_with($url, '/')) {
            return $publicDomain . $url;
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            $parts = parse_url($url);
            $path = $parts['path'] ?? '';
            $query = isset($parts['query']) ? ('?' . $parts['query']) : '';
            $fragment = isset($parts['fragment']) ? ('#' . $parts['fragment']) : '';

            return $publicDomain . $path . $query . $fragment;
        }

        return $publicDomain . '/' . ltrim($url, '/');
    }

    public function allSettings(): array
    {
        return [
            'webhook_mode' => $this->webhookMode(),
            'webhook_url_test' => $this->setting(
                self::KEY_WEBHOOK_URL_TEST,
                (string) config('services.n8n.webhook_url_test', (string) config('services.n8n.webhook_url', ''))
            ),
            'webhook_url_production' => $this->setting(
                self::KEY_WEBHOOK_URL_PRODUCTION,
                (string) config('services.n8n.webhook_url_production', (string) config('services.n8n.webhook_url', ''))
            ),
            'webhook_url_group2_test' => $this->setting(
                self::KEY_WEBHOOK_URL_GROUP2_TEST,
                (string) config('services.n8n.webhook_url_group2_test', (string) config('services.n8n.webhook_url_group2', ''))
            ),
            'webhook_url_group2_production' => $this->setting(
                self::KEY_WEBHOOK_URL_GROUP2_PRODUCTION,
                (string) config('services.n8n.webhook_url_group2_production', (string) config('services.n8n.webhook_url_group2', ''))
            ),
            'text_program_created' => $this->template(self::KEY_TEXT_PROGRAM_CREATED),
            'text_salary_request' => $this->template(self::KEY_TEXT_SALARY_REQUEST),
            'text_pasti_info_request' => $this->template(self::KEY_TEXT_PASTI_INFO_REQUEST),
            'text_guru_course_offer' => $this->template(self::KEY_TEXT_GURU_COURSE_OFFER),
            'text_leave_notice_submitted' => $this->template(self::KEY_TEXT_LEAVE_NOTICE_SUBMITTED),
            'text_claim_submitted' => $this->template(self::KEY_TEXT_CLAIM_SUBMITTED),
        ];
    }

    private function activeWebhookUrl(bool $isGroup2): string
    {
        $mode = $this->webhookMode();

        if ($isGroup2) {
            return $mode === 'test'
                ? $this->setting(
                    self::KEY_WEBHOOK_URL_GROUP2_TEST,
                    (string) config('services.n8n.webhook_url_group2_test', (string) config('services.n8n.webhook_url_group2', ''))
                )
                : $this->setting(
                    self::KEY_WEBHOOK_URL_GROUP2_PRODUCTION,
                    (string) config('services.n8n.webhook_url_group2_production', (string) config('services.n8n.webhook_url_group2', ''))
                );
        }

        return $mode === 'test'
            ? $this->setting(
                self::KEY_WEBHOOK_URL_TEST,
                (string) config('services.n8n.webhook_url_test', (string) config('services.n8n.webhook_url', ''))
            )
            : $this->setting(
                self::KEY_WEBHOOK_URL_PRODUCTION,
                (string) config('services.n8n.webhook_url_production', (string) config('services.n8n.webhook_url', ''))
            );
    }

    private function webhookMode(): string
    {
        $mode = strtolower($this->setting(self::KEY_WEBHOOK_MODE, 'production'));

        return in_array($mode, ['test', 'production'], true) ? $mode : 'production';
    }

    private function template(string $key): string
    {
        return match ($key) {
            self::KEY_TEXT_PROGRAM_CREATED => $this->setting($key, self::DEFAULT_TEXT_PROGRAM_CREATED),
            self::KEY_TEXT_SALARY_REQUEST => $this->setting($key, self::DEFAULT_TEXT_SALARY_REQUEST),
            self::KEY_TEXT_PASTI_INFO_REQUEST => $this->setting($key, self::DEFAULT_TEXT_PASTI_INFO_REQUEST),
            self::KEY_TEXT_GURU_COURSE_OFFER => $this->setting($key, self::DEFAULT_TEXT_GURU_COURSE_OFFER),
            self::KEY_TEXT_LEAVE_NOTICE_SUBMITTED => $this->setting($key, self::DEFAULT_TEXT_LEAVE_NOTICE_SUBMITTED),
            self::KEY_TEXT_CLAIM_SUBMITTED => $this->setting($key, self::DEFAULT_TEXT_CLAIM_SUBMITTED),
            default => '',
        };
    }

    private function setting(string $key, string $default): string
    {
        try {
            $stored = SystemSetting::query()->where('key', $key)->value('value');
            $value = is_string($stored) ? trim($stored) : '';
        } catch (Throwable) {
            return $default;
        }

        return $value !== '' ? $value : $default;
    }

    private function renderTemplate(string $template, array $variables): string
    {
        $replacements = [];
        foreach ($variables as $key => $value) {
            $replacements['{' . $key . '}'] = (string) $value;
        }

        return trim((string) strtr($template, $replacements));
    }
}
