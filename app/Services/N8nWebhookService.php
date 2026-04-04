<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class N8nWebhookService
{
    public function send(string $text, ?string $link = null, ?string $gambar = null): void
    {
        $webhookUrl = (string) config('services.n8n.webhook_url', '');
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
}
