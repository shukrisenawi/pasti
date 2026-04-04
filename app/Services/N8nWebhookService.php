<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class N8nWebhookService
{
    public const KEY_WEBHOOK_URL = 'n8n_webhook_url';
    public const KEY_TEXT_PROGRAM_CREATED = 'n8n_text_program_created';
    public const KEY_TEXT_SALARY_REQUEST = 'n8n_text_salary_request';
    public const KEY_TEXT_PASTI_INFO_REQUEST = 'n8n_text_pasti_info_request';
    public const KEY_TEXT_GURU_COURSE_OFFER = 'n8n_text_guru_course_offer';

    private const DEFAULT_TEXT_PROGRAM_CREATED = '{tajuk} akan diadakan pada {tarikh} ({hari}){masa}{lokasi}.';
    private const DEFAULT_TEXT_SALARY_REQUEST = 'Permintaan kemaskini gaji guru telah dihantar pada {tarikh}. Sila kemaskini gaji dan elaun semasa.';
    private const DEFAULT_TEXT_PASTI_INFO_REQUEST = 'Permintaan kemaskini maklumat PASTI telah dihantar pada {tarikh}. Sila lengkapkan maklumat terkini PASTI.';
    private const DEFAULT_TEXT_GURU_COURSE_OFFER = 'Permintaan sambung Kursus Guru ke Semester {semester} telah dihantar. Tarikh akhir pendaftaran: {tarikh_akhir}.{nota}';

    public function send(string $text, ?string $link = null, ?string $gambar = null): void
    {
        $webhookUrl = $this->setting(self::KEY_WEBHOOK_URL, (string) config('services.n8n.webhook_url', ''));
        if ($webhookUrl === '') {
            return;
        }

        $payload = [
            'text' => $text,
            'gambar' => $gambar,
            'link' => $link,
        ];

        try {
            Http::asJson()
                ->acceptJson()
                ->timeout(10)
                ->post($webhookUrl, $payload);
        } catch (Throwable $e) {
            Log::warning('Gagal hantar webhook n8n.', [
                'message' => $e->getMessage(),
                'webhook_url' => $webhookUrl,
                'payload' => $payload,
            ]);
        }
    }

    public function sendByTemplate(string $templateKey, array $variables, ?string $link = null, ?string $gambar = null): void
    {
        $template = $this->template($templateKey);
        $text = $this->renderTemplate($template, $variables);
        $this->send($text, $link, $gambar);
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
            'webhook_url' => $this->setting(self::KEY_WEBHOOK_URL, (string) config('services.n8n.webhook_url', '')),
            'text_program_created' => $this->template(self::KEY_TEXT_PROGRAM_CREATED),
            'text_salary_request' => $this->template(self::KEY_TEXT_SALARY_REQUEST),
            'text_pasti_info_request' => $this->template(self::KEY_TEXT_PASTI_INFO_REQUEST),
            'text_guru_course_offer' => $this->template(self::KEY_TEXT_GURU_COURSE_OFFER),
        ];
    }

    private function template(string $key): string
    {
        return match ($key) {
            self::KEY_TEXT_PROGRAM_CREATED => $this->setting($key, self::DEFAULT_TEXT_PROGRAM_CREATED),
            self::KEY_TEXT_SALARY_REQUEST => $this->setting($key, self::DEFAULT_TEXT_SALARY_REQUEST),
            self::KEY_TEXT_PASTI_INFO_REQUEST => $this->setting($key, self::DEFAULT_TEXT_PASTI_INFO_REQUEST),
            self::KEY_TEXT_GURU_COURSE_OFFER => $this->setting($key, self::DEFAULT_TEXT_GURU_COURSE_OFFER),
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
