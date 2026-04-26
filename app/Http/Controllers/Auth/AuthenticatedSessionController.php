<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    private const LAST_LOGIN_USER_COOKIE = 'last_login_user_id';

    /**
     * Display the login view.
     */
    public function create(Request $request): View|RedirectResponse
    {
        if ($request->boolean('switch_user')) {
            Cookie::queue(Cookie::forget(self::LAST_LOGIN_USER_COOKIE));

            return redirect()->route('login');
        }

        $lastLoginUser = null;
        $lastLoginUserId = $request->cookie(self::LAST_LOGIN_USER_COOKIE);

        if (is_numeric($lastLoginUserId)) {
            $lastLoginUser = User::query()
                ->select(['id', 'name', 'nama_samaran', 'email', 'avatar_path'])
                ->find((int) $lastLoginUserId);

            if ($lastLoginUser) {
                $lastLoginUser->setAttribute(
                    'login_unread_notifications_count',
                    $lastLoginUser->unreadNotifications()->count()
                );
            }
        }

        return view('auth.login', [
            'lastLoginUser' => $lastLoginUser,
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();
        $request->session()->forget('active_role_mode');
        $request->session()->put('login_using_admin_role', Auth::user()?->hasAnyRole(['master_admin', 'admin']) ?? false);

        User::query()
            ->whereKey(Auth::id())
            ->update([
                'last_login_at' => now(),
            ]);

        Cookie::queue(
            self::LAST_LOGIN_USER_COOKIE,
            (string) Auth::id(),
            60 * 24 * 30
        );

        $user = Auth::user();
        if ($user && $user->hasAnyRole(['master_admin', 'admin'])) {
            $guruExists = \App\Models\Guru::query()->where('email', $user->email)->exists();
            if ($guruExists) {
                if (! $user->hasRole('guru')) {
                    $user->assignRole('guru');
                }
                \App\Models\Guru::query()
                    ->where('email', $user->email)
                    ->where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', '<>', $user->id))
                    ->update(['user_id' => $user->id]);
            }
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Cookie::queue(
            self::LAST_LOGIN_USER_COOKIE,
            (string) Auth::id(),
            60 * 24 * 30
        );

        $request->session()->forget(['active_role_mode', 'login_using_admin_role']);

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
