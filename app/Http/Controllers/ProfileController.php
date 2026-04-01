<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Pasti;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();

        return view('profile.edit', [
            'user' => $user,
            'pastis' => $user->hasRole('guru')
                ? Pasti::query()->orderBy('name')->get(['id', 'name'])
                : collect(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        unset(
            $data['avatar'],
            $data['remove_avatar'],
            $data['pasti_id'],
            $data['phone'],
            $data['marital_status'],
            $data['joined_at']
        );

        $data['nama_samaran'] = $data['nama_samaran'] ?? $data['name'];

        $user->fill($data);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        if ($request->boolean('remove_avatar') && $user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
            $user->avatar_path = null;
        }

        if ($request->hasFile('avatar')) {
            if ($user->avatar_path) {
                Storage::disk('public')->delete($user->avatar_path);
            }

            $user->avatar_path = $request->file('avatar')->store('avatars', 'public');
        }

        $user->save();

        if ($user->hasRole('guru') && $user->guru) {
            $user->guru->update([
                'pasti_id' => $request->input('pasti_id') ?: null,
                'phone' => $request->input('phone') ?: null,
                'marital_status' => $request->input('marital_status') ?: null,
                'joined_at' => $request->input('joined_at') ?: null,
            ]);
        }

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }
}
