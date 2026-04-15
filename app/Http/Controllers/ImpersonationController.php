<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

class ImpersonationController extends Controller
{
    private const IMPERSONATOR_COOKIE = 'impersonator_user_id';
    private const RETURN_URL_COOKIE = 'impersonator_return_url';
    private const TOKEN_COOKIE = 'impersonation_token';

    public function start(Request $request, Guru $users_guru): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor->hasAnyRole(['master_admin', 'admin']), 403);

        $targetUser = $users_guru->user;
        abort_unless($targetUser && $targetUser->hasRole('guru'), 404);

        if ($targetUser->id === $actor->id) {
            return redirect()->route('users.gurus.index');
        }

        return $this->beginImpersonation(
            $request,
            $targetUser,
            route('users.gurus.index'),
            'Anda kini melihat sistem sebagai guru.'
        );
    }

    public function startAdmin(Request $request, User $users_admin): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor->hasRole('master_admin'), 403);
        abort_unless($users_admin->hasRole('admin'), 404);

        if ($users_admin->id === $actor->id) {
            return redirect()->route('users.admins.index');
        }

        return $this->beginImpersonation(
            $request,
            $users_admin,
            route('users.admins.index'),
            'Anda kini melihat sistem sebagai admin.'
        );
    }

    public function stop(Request $request): RedirectResponse
    {
        $token = (string) ($request->session()->pull('impersonation_token', '') ?: $request->cookie(self::TOKEN_COOKIE, ''));
        $cachedData = $token !== '' ? (array) Cache::pull('impersonation:' . $token, []) : [];
        $impersonatorId = (int) ($request->session()->pull('impersonator_user_id', 0) ?: $request->cookie(self::IMPERSONATOR_COOKIE, 0));
        $returnUrl = (string) ($request->session()->pull('impersonator_return_url', '') ?: $request->cookie(self::RETURN_URL_COOKIE, route('users.gurus.index')));
        if ($impersonatorId <= 0) {
            $impersonatorId = (int) ($cachedData['impersonator_user_id'] ?? 0);
        }
        if ($returnUrl === '') {
            $returnUrl = (string) ($cachedData['impersonator_return_url'] ?? route('users.gurus.index'));
        }

        if ($impersonatorId <= 0) {
            return redirect()->route('dashboard')->with('status', 'Sesi masuk sebagai pengguna lain telah tamat.');
        }

        $impersonator = User::query()->find($impersonatorId);
        if (! $impersonator || ! $impersonator->hasAnyRole(['master_admin', 'admin'])) {
            return redirect()->route('dashboard')->with('status', 'Akaun admin asal tidak dijumpai.');
        }

        Auth::login($impersonator);
        $request->session()->regenerate();
        Cookie::queue(Cookie::forget(self::IMPERSONATOR_COOKIE));
        Cookie::queue(Cookie::forget(self::RETURN_URL_COOKIE));
        Cookie::queue(Cookie::forget(self::TOKEN_COOKIE));

        return redirect()->to($returnUrl)->with('status', 'Kembali semula ke akaun admin.');
    }

    private function beginImpersonation(Request $request, User $targetUser, string $defaultReturnUrl, string $statusMessage): RedirectResponse
    {
        $actor = $request->user();

        Auth::login($targetUser);
        $request->session()->regenerate();
        $returnUrl = (string) $request->input('return_to', $defaultReturnUrl);
        $token = Str::uuid()->toString();

        $request->session()->put([
            'impersonator_user_id' => $actor->id,
            'impersonator_return_url' => $returnUrl,
            'impersonation_token' => $token,
        ]);
        Cookie::queue(self::IMPERSONATOR_COOKIE, (string) $actor->id, 60);
        Cookie::queue(self::RETURN_URL_COOKIE, $returnUrl, 60);
        Cookie::queue(self::TOKEN_COOKIE, $token, 60);
        Cache::put('impersonation:' . $token, [
            'impersonator_user_id' => $actor->id,
            'impersonator_return_url' => $returnUrl,
        ], now()->addHours(2));

        return redirect()->route('dashboard')->with('status', $statusMessage);
    }
}
