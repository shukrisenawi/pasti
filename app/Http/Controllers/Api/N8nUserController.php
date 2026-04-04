<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class N8nUserController extends Controller
{
    public function birthdates(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ]);

        $limit = (int) ($validated['limit'] ?? 500);

        $users = User::query()
            ->whereNotNull('tarikh_lahir')
            ->orderByRaw("DATE_FORMAT(tarikh_lahir, '%m-%d')")
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'nama' => $user->display_name,
                'tarikh_lahir' => optional($user->tarikh_lahir)?->format('Y-m-d'),
            ]);

        return response()->json([
            'data' => $users,
            'meta' => [
                'count' => $users->count(),
                'limit' => $limit,
            ],
        ]);
    }
}
