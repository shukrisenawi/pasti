<?php

namespace App\Http\Controllers;

use App\Models\FcmToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebViewFcmTokenController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'fcm_token' => ['required', 'string', 'max:4096'],
            'device_name' => ['nullable', 'string', 'max:255'],
            'platform' => ['nullable', 'string', 'max:50'],
        ]);

        FcmToken::query()->updateOrCreate(
            ['token' => $data['fcm_token']],
            [
                'user_id' => $request->user()->id,
                'device_name' => $data['device_name'] ?? null,
                'platform' => $data['platform'] ?? null,
                'last_used_at' => now(),
            ],
        );

        return response()->json([
            'message' => 'Token FCM berjaya didaftarkan.',
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate([
            'fcm_token' => ['required', 'string', 'max:4096'],
        ]);

        FcmToken::query()
            ->where('token', $data['fcm_token'])
            ->delete();

        return response()->json([
            'message' => 'Token FCM berjaya dibuang.',
        ]);
    }
}
