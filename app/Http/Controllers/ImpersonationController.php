<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonationController extends Controller
{
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

        return redirect()->route('dashboard')->with('status', 'Anda kini melihat sistem sebagai guru.');
    }

    public function stop(Request $request): RedirectResponse
    {
        $impersonatorId = (int) $request->session()->pull('impersonator_user_id', 0);
        $returnUrl = (string) $request->session()->pull('impersonator_return_url', route('users.gurus.index'));
        abort_if($impersonatorId <= 0, 403);

        $impersonator = User::query()->find($impersonatorId);
        abort_unless($impersonator && $impersonator->hasAnyRole(['master_admin', 'admin']), 403);

        Auth::login($impersonator);
        $request->session()->regenerate();

        return redirect()->to($returnUrl)->with('status', 'Kembali semula ke akaun admin.');
    }
}

