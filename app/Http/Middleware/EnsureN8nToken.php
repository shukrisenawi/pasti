<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureN8nToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $configuredToken = (string) config('services.n8n.api_token', '');

        if ($configuredToken === '') {
            return new JsonResponse([
                'message' => 'N8N API token is not configured.',
            ], 500);
        }

        $providedToken = $request->bearerToken() ?: (string) $request->header('X-N8N-TOKEN', '');

        if ($providedToken === '' || ! hash_equals($configuredToken, $providedToken)) {
            return new JsonResponse([
                'message' => 'Unauthorized.',
            ], 401);
        }

        return $next($request);
    }
}

