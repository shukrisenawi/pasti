<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminMode
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isOperatingAsAdmin()) {
            return $next($request);
        }

        return redirect()
            ->route('dashboard')
            ->with('status', 'Akses halaman admin tidak tersedia semasa anda sedang dalam mod guru.');
    }
}
