<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class FirebaseAccessTokenService
{
    public function isConfigured(): bool
    {
        $credentials = $this->credentials();

        return filled(config('services.firebase.project_id'))
            && filled($credentials['client_email'] ?? null)
            && filled($credentials['private_key'] ?? null);
    }

    public function getAccessToken(): ?string
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $credentials = $this->credentials();
        $tokenUri = $credentials['token_uri'] ?? 'https://oauth2.googleapis.com/token';
        $cacheKey = 'firebase-access-token-'.md5((string) config('services.firebase.project_id').($credentials['client_email'] ?? ''));

        return Cache::remember($cacheKey, now()->addMinutes(50), function () use ($credentials, $tokenUri): string {
            $now = time();
            $claims = [
                'iss' => $credentials['client_email'],
                'sub' => $credentials['client_email'],
                'aud' => $tokenUri,
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'iat' => $now,
                'exp' => $now + 3600,
            ];

            $jwt = $this->encodeJwt([
                'alg' => 'RS256',
                'typ' => 'JWT',
            ], $claims, $credentials['private_key']);

            $response = Http::asForm()
                ->timeout(15)
                ->post($tokenUri, [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $jwt,
                ])
                ->throw()
                ->json();

            $accessToken = $response['access_token'] ?? null;
            if (! is_string($accessToken) || $accessToken === '') {
                throw new RuntimeException('Token akses Firebase tidak diterima.');
            }

            return $accessToken;
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function credentials(): array
    {
        $rawJson = config('services.firebase.service_account_json');
        if (is_string($rawJson) && trim($rawJson) !== '') {
            $decoded = json_decode($rawJson, true);

            return is_array($decoded) ? $decoded : [];
        }

        $path = config('services.firebase.service_account_path');
        if (! is_string($path) || trim($path) === '') {
            return [];
        }

        try {
            if (! is_file($path)) {
                return [];
            }

            $decoded = json_decode((string) file_get_contents($path), true);
        } catch (Throwable) {
            return [];
        }

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param  array<string, mixed>  $header
     * @param  array<string, mixed>  $payload
     */
    private function encodeJwt(array $header, array $payload, string $privateKey): string
    {
        $segments = [
            $this->base64UrlEncode(json_encode($header, JSON_UNESCAPED_SLASHES)),
            $this->base64UrlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES)),
        ];

        $signingInput = implode('.', $segments);
        $signature = '';

        $success = openssl_sign($signingInput, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        if (! $success) {
            throw new RuntimeException('Gagal menandatangani JWT Firebase.');
        }

        $segments[] = $this->base64UrlEncode($signature);

        return implode('.', $segments);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
