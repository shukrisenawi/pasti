<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;

class ImpersonationController extends Controller
{
    private const IMPERSONATOR_COOKIE = 'impersonator_user_id';
    private const RETURN_URL_COOKIE = 'impersonator_return_url';

    public function start(Request $request, Guru $users_guru): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor->hasAnyRole(['master_admin', 'admin']), 403);

        $targetUser = $users_guru->user;
        abort_unless($targetUser && $targetUser->hasRole('guru'), 404);

        if ($targetUser->id === $actor->id) {
            return redirect()->route('users.gurus.index');
        }

        Auth::login($targetUser);
        $request->session()->regenerate();
        $request->session()->put([
            'impersonator_user_id' => $actor->id,
            'impersonator_return_url' => (string) $request->input('return_to', route('users.gurus.index')),
        ]);
        Cookie::queue(self::IMPERSONATOR_COOKIE, (string) $actor->id, 60);
        Cookie::queue(self::RETURN_URL_COOKIE, (string) $request->input('return_to', route('users.gurus.index')), 60);

        return redirect()->route('dashboard')->with('status', 'Anda kini melihat sistem sebagai guru.');
    }

    public function stop(Request $request): RedirectResponse
    {
        $impersonatorId = (int) ($request->session()->pull('impersonator_user_id', 0) ?: $request->cookie(self::IMPERSONATOR_COOKIE, 0));
        $returnUrl = (string) ($request->session()->pull('impersonator_return_url', '') ?: $request->cookie(self::RETURN_URL_COOKIE, route('users.gurus.index')));

        if ($impersonatorId <= 0) {
            return redirect()->route('dashboard')->with('status', 'Sesi masuk sebagai guru telah tamat.');
        }

        $impersonator = User::query()->find($impersonatorId);
        if (! $impersonator || ! $impersonator->hasAnyRole(['master_admin', 'admin'])) {
            return redirect()->route('dashboard')->with('status', 'Akaun admin asal tidak dijumpai.');
        }

        Auth::login($impersonator);
        $request->session()->regenerate();
        Cookie::queue(Cookie::forget(self::IMPERSONATOR_COOKIE));
        Cookie::queue(Cookie::forget(self::RETURN_URL_COOKIE));

        return redirect()->to($returnUrl)->with('status', 'Kembali semula ke akaun admin.');
    }
}
