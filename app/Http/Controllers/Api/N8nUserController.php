<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\N8nWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class N8nUserController extends Controller
{
    public function birthdates(Request $request, N8nWebhookService $n8nWebhookService): JsonResponse
    {
        $validated = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ]);

        $limit = (int) ($validated['limit'] ?? 500);

        $users = User::query()
            ->whereNotNull('tarikh_lahir')
            ->orderByRaw("DATE_FORMAT(tarikh_lahir, '%m-%d')")
            ->orderBy('name')
            ->get()
            ->unique(fn (User $user) => strtolower(trim((string) $user->display_name)) . '|' . optional($user->tarikh_lahir)?->format('Y-m-d'))
            ->take($limit)
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'nama' => $user->display_name,
                'tarikh_lahir' => optional($user->tarikh_lahir)?->format('Y-m-d'),
                'avatar_url' => $this->publicUrl($user->avatar_url),
            ]);

        return response()->json([
            'data' => $users,
            'meta' => [
                'count' => $users->count(),
                'limit' => $limit,
                'group_active_whatsapp' => $n8nWebhookService->allSettings()['webhook_group'] ?? 'real',
            ],
        ]);
    }

    private function publicUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $publicDomain = rtrim((string) config('services.n8n.public_domain', 'https://pastikawasansik.my.id'), '/');

        if (str_starts_with($path, '/')) {
            return $publicDomain . $path;
        }

        return $publicDomain . '/' . ltrim($path, '/');
    }
}
