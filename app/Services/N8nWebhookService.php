<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class N8nWebhookService
{
    public const KEY_WEBHOOK_MODE = 'n8n_webhook_mode';
    public const KEY_WEBHOOK_GROUP = 'n8n_webhook_group';

    public const KEY_WEBHOOK_URL_TEST = 'n8n_webhook_url_test';
    public const KEY_WEBHOOK_URL_PRODUCTION = 'n8n_webhook_url_production';
    public const KEY_WEBHOOK_URL_GROUP2_TEST = 'n8n_webhook_url_group2_test';
    public const KEY_WEBHOOK_URL_GROUP2_PRODUCTION = 'n8n_webhook_url_group2_production';

    public const KEY_TEXT_PROGRAM_CREATED = 'n8n_text_program_created';
    public const KEY_TEXT_SALARY_REQUEST = 'n8n_text_salary_request';
    public const KEY_TEXT_PASTI_INFO_REQUEST = 'n8n_text_pasti_info_request';
    public const KEY_TEXT_PASTI_INFO_RESPONSE_REMINDER = 'n8n_text_pasti_info_response_reminder';
    public const KEY_TEXT_GURU_COURSE_OFFER = 'n8n_text_guru_course_offer';
    public const KEY_TEXT_GURU_COURSE_RESPONSE_REMINDER = 'n8n_text_guru_course_response_reminder';
    public const KEY_TEXT_LEAVE_NOTICE_SUBMITTED = 'n8n_text_leave_notice_submitted';
    public const KEY_TEXT_CLAIM_SUBMITTED = 'n8n_text_claim_submitted';
    public const KEY_TEXT_ALL_PASTI_INFO_COMPLETED = 'n8n_text_all_pasti_info_completed';
    public const KEY_TEXT_ALL_GURU_SALARY_COMPLETED = 'n8n_text_all_guru_salary_completed';
    public const KEY_TEXT_GURU_SALARY_RESPONSE_REMINDER = 'n8n_text_guru_salary_response_reminder';
    public const KEY_TEXT_ADMIN_BROADCAST = 'n8n_text_admin_broadcast';
    public const KEY_TEXT_GURU_MESSAGE_TO_ADMIN = 'n8n_text_guru_message_to_admin';
    public const KEY_TEXT_DIRECTORY_FILE_ALL_GURU = 'n8n_text_directory_file_all_guru';
    public const KEY_TEXT_ANNOUNCEMENT_ALL_GURU = 'n8n_text_announcement_all_guru';

    private const DEFAULT_TEXT_PROGRAM_CREATED = '{tajuk} akan diadakan pada {tarikh} ({hari}){masa}{lokasi}.';
    private const DEFAULT_TEXT_SALARY_REQUEST = 'Permintaan kemaskini elaun guru telah dihantar pada {tarikh}. Sila kemaskini elaun dan elaun tambahan semasa.';
    private const DEFAULT_TEXT_PASTI_INFO_REQUEST = 'Permintaan kemaskini maklumat PASTI telah dihantar pada {tarikh}. Sila lengkapkan maklumat terkini PASTI.';
    private const DEFAULT_TEXT_PASTI_INFO_RESPONSE_REMINDER = "Sila hantar respon maklumat PASTI segera.\n\nPASTI yang belum respon:\n{senarai_pasti}";
    private const DEFAULT_TEXT_GURU_COURSE_OFFER = 'Permintaan sambung Kursus Guru ke Semester {semester} telah dihantar. Tarikh akhir pendaftaran: {tarikh_akhir}.{nota}';
    private const DEFAULT_TEXT_GURU_COURSE_RESPONSE_REMINDER = "Sila hantar respon sambung kursus guru segera.\n\nGuru yang belum respon:\n{senarai_guru}";
    private const DEFAULT_TEXT_LEAVE_NOTICE_SUBMITTED = '{nama_guru} hantar notis cuti pada {tarikh_cuti} hingga {tarikh_hingga}. Sebab: {sebab}.';
    private const DEFAULT_TEXT_CLAIM_SUBMITTED = '{nama_guru} hantar claim sebanyak RM{jumlah} pada {tarikh_claim}. Catatan: {catatan}.';
    private const DEFAULT_TEXT_ALL_PASTI_INFO_COMPLETED = 'Semua PASTI telah hantar maklumat PASTI. Dikemaskini pada {tarikh}.';
    private const DEFAULT_TEXT_ALL_GURU_SALARY_COMPLETED = 'Semua guru telah hantar maklumat elaun semasa. Dikemaskini pada {tarikh}.';
    private const DEFAULT_TEXT_GURU_SALARY_RESPONSE_REMINDER = "Sila hantar respon maklumat gaji guru segera.\n\nGuru yang belum respon:\n{senarai_guru}";
    private const DEFAULT_TEXT_ADMIN_BROADCAST = '{nama_penghantar} hantar hebahan kepada {jumlah_guru} guru. Mesej: {mesej}.';
    private const DEFAULT_TEXT_GURU_MESSAGE_TO_ADMIN = '{nama_guru} dari {pasti} hantar mesej kepada admin. Mesej: {mesej}.';
    private const DEFAULT_TEXT_DIRECTORY_FILE_ALL_GURU = '{nama_penghantar} muat naik fail directory untuk semua guru: {nama_fail}.';
    private const DEFAULT_TEXT_ANNOUNCEMENT_ALL_GURU = '{nama_penghantar} hantar pengumuman kepada {jumlah_guru} guru: {tajuk}. Tamat pada {tarikh_tamat}.';

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
            'group' => $this->webhookGroup(),
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

    public function toActionUrl(?string $url): ?string
    {
        if (! filled($url)) {
            return null;
        }

        $publicUrl = $this->toPublicUrl($url);

        if (! filled($publicUrl)) {
            return null;
        }

        $linkTarget = strtolower((string) config('services.n8n.link_target', 'web'));

        if ($linkTarget !== 'app') {
            return $publicUrl;
        }

        return $this->renderActionLinkTemplate($publicUrl);
    }

    public function androidAssetLinks(): array
    {
        $packageName = trim((string) config('services.n8n.android_app_package', ''));
        $fingerprints = array_values(array_filter(
            (array) config('services.n8n.android_sha256_cert_fingerprints', []),
            static fn ($value) => is_string($value) && trim($value) !== ''
        ));

        if ($packageName === '' || $fingerprints === []) {
            return [];
        }

        return [[
            'relation' => ['delegate_permission/common.handle_all_urls'],
            'target' => [
                'namespace' => 'android_app',
                'package_name' => $packageName,
                'sha256_cert_fingerprints' => $fingerprints,
            ],
        ]];
    }

    public function allSettings(): array
    {
        return [
            'webhook_mode' => $this->webhookMode(),
            'webhook_group' => $this->webhookGroup(),
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
            'text_pasti_info_response_reminder' => $this->template(self::KEY_TEXT_PASTI_INFO_RESPONSE_REMINDER),
            'text_guru_course_offer' => $this->template(self::KEY_TEXT_GURU_COURSE_OFFER),
            'text_guru_course_response_reminder' => $this->template(self::KEY_TEXT_GURU_COURSE_RESPONSE_REMINDER),
            'text_leave_notice_submitted' => $this->template(self::KEY_TEXT_LEAVE_NOTICE_SUBMITTED),
            'text_claim_submitted' => $this->template(self::KEY_TEXT_CLAIM_SUBMITTED),
            'text_all_pasti_info_completed' => $this->template(self::KEY_TEXT_ALL_PASTI_INFO_COMPLETED),
            'text_all_guru_salary_completed' => $this->template(self::KEY_TEXT_ALL_GURU_SALARY_COMPLETED),
            'text_guru_salary_response_reminder' => $this->template(self::KEY_TEXT_GURU_SALARY_RESPONSE_REMINDER),
            'text_admin_broadcast' => $this->template(self::KEY_TEXT_ADMIN_BROADCAST),
            'text_guru_message_to_admin' => $this->template(self::KEY_TEXT_GURU_MESSAGE_TO_ADMIN),
            'text_directory_file_all_guru' => $this->template(self::KEY_TEXT_DIRECTORY_FILE_ALL_GURU),
            'text_announcement_all_guru' => $this->template(self::KEY_TEXT_ANNOUNCEMENT_ALL_GURU),
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

    private function webhookGroup(): string
    {
        $group = strtolower($this->setting(self::KEY_WEBHOOK_GROUP, 'real'));

        return in_array($group, ['test', 'real'], true) ? $group : 'real';
    }

    private function template(string $key): string
    {
        return match ($key) {
            self::KEY_TEXT_PROGRAM_CREATED => $this->setting($key, self::DEFAULT_TEXT_PROGRAM_CREATED),
            self::KEY_TEXT_SALARY_REQUEST => $this->setting($key, self::DEFAULT_TEXT_SALARY_REQUEST),
            self::KEY_TEXT_PASTI_INFO_REQUEST => $this->setting($key, self::DEFAULT_TEXT_PASTI_INFO_REQUEST),
            self::KEY_TEXT_PASTI_INFO_RESPONSE_REMINDER => $this->setting($key, self::DEFAULT_TEXT_PASTI_INFO_RESPONSE_REMINDER),
            self::KEY_TEXT_GURU_COURSE_OFFER => $this->setting($key, self::DEFAULT_TEXT_GURU_COURSE_OFFER),
            self::KEY_TEXT_GURU_COURSE_RESPONSE_REMINDER => $this->setting($key, self::DEFAULT_TEXT_GURU_COURSE_RESPONSE_REMINDER),
            self::KEY_TEXT_LEAVE_NOTICE_SUBMITTED => $this->setting($key, self::DEFAULT_TEXT_LEAVE_NOTICE_SUBMITTED),
            self::KEY_TEXT_CLAIM_SUBMITTED => $this->setting($key, self::DEFAULT_TEXT_CLAIM_SUBMITTED),
            self::KEY_TEXT_ALL_PASTI_INFO_COMPLETED => $this->setting($key, self::DEFAULT_TEXT_ALL_PASTI_INFO_COMPLETED),
            self::KEY_TEXT_ALL_GURU_SALARY_COMPLETED => $this->setting($key, self::DEFAULT_TEXT_ALL_GURU_SALARY_COMPLETED),
            self::KEY_TEXT_GURU_SALARY_RESPONSE_REMINDER => $this->setting($key, self::DEFAULT_TEXT_GURU_SALARY_RESPONSE_REMINDER),
            self::KEY_TEXT_ADMIN_BROADCAST => $this->setting($key, self::DEFAULT_TEXT_ADMIN_BROADCAST),
            self::KEY_TEXT_GURU_MESSAGE_TO_ADMIN => $this->setting($key, self::DEFAULT_TEXT_GURU_MESSAGE_TO_ADMIN),
            self::KEY_TEXT_DIRECTORY_FILE_ALL_GURU => $this->setting($key, self::DEFAULT_TEXT_DIRECTORY_FILE_ALL_GURU),
            self::KEY_TEXT_ANNOUNCEMENT_ALL_GURU => $this->setting($key, self::DEFAULT_TEXT_ANNOUNCEMENT_ALL_GURU),
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

    private function renderActionLinkTemplate(string $publicUrl): string
    {
        $template = trim((string) config('services.n8n.action_link_template', '{public_url}'));

        if ($template === '') {
            return $publicUrl;
        }

        $parts = parse_url($publicUrl);
        $path = (string) ($parts['path'] ?? '/');
        $query = isset($parts['query']) ? ('?' . $parts['query']) : '';
        $fragment = isset($parts['fragment']) ? ('#' . $parts['fragment']) : '';

        return $this->renderTemplate($template, [
            'public_url' => $publicUrl,
            'encoded_public_url' => rawurlencode($publicUrl),
            'path' => $path,
            'encoded_path' => rawurlencode($path),
            'query' => $query,
            'encoded_query' => rawurlencode($query),
            'fragment' => $fragment,
            'encoded_fragment' => rawurlencode($fragment),
        ]);
    }
}
