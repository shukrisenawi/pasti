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

        $status = $this->profileCompletionService->onboardingStatus($user);
        if (! $status['onboarding_completed']) {
            return response()->json([
                'message' => 'Sila lengkapkan profil, kemaskini maklumat PASTI dan tukar kata laluan sebelum menggunakan fungsi lain.',
                'code' => 'ONBOARDING_INCOMPLETE',
                'profile_completed' => $status['profile_completed'],
                'pasti_completed' => $status['pasti_completed'],
                'missing_fields' => $status['missing_fields'],
                'missing_pasti_fields' => $status['missing_pasti_fields'],
                'password_change_required' => $status['password_change_required'],
            ], 428);
        }

        return $next($request);
    }
}
