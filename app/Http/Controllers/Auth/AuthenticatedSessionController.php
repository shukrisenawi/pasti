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

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
