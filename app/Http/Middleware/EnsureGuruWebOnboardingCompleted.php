<?php

namespace App\Http\Middleware;

use App\Support\GuruProfileCompletionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureGuruWebOnboardingCompleted
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

        $status = $this->profileCompletionService->onboardingStatus($user);
        if ($status['onboarding_completed']) {
            return $next($request);
        }

        $allowed = [
            'profile.edit',
            'profile.update',
            'profile.pasti-selection.update',
            'pasti.self.edit',
            'pasti.self.update',
            'password.update',
            'logout',
            'locale.update',
            'verification.send',
        ];

        if ($request->routeIs($allowed)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Sila lengkapkan profil, pilih PASTI dan tukar kata laluan sebelum menggunakan fungsi lain.',
                'code' => 'ONBOARDING_INCOMPLETE',
                'profile_completed' => $status['profile_completed'],
                'pasti_completed' => $status['pasti_completed'],
                'missing_fields' => $status['missing_fields'],
                'missing_pasti_fields' => $status['missing_pasti_fields'],
                'password_change_required' => $status['password_change_required'],
            ], 428);
        }

        return redirect()
            ->route('profile.edit')
            ->with('onboarding_notice', 'Sila lengkapkan profil, pilih PASTI dan tukar kata laluan dahulu sebelum menggunakan fungsi lain.');
    }
}
