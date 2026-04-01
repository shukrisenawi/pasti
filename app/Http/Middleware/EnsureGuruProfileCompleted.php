<?php

namespace App\Http\Middleware;

use App\Support\GuruProfileCompletionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureGuruProfileCompleted
{
    public function __construct(private readonly GuruProfileCompletionService $profileCompletionService)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole('guru')) {
            return $next($request);
        }

        if (! $user->guru) {
            return response()->json(['message' => 'Guru profile not found.'], 404);
        }

        $missingFields = $this->profileCompletionService->missingFields($user);
        if ($missingFields !== []) {
            return response()->json([
                'message' => 'Sila lengkapkan profil anda sebelum menggunakan fungsi lain.',
                'code' => 'PROFILE_INCOMPLETE',
                'missing_fields' => $missingFields,
            ], 428);
        }

        return $next($request);
    }
}
