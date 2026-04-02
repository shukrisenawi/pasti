<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Kawasan;
use App\Support\GuruProfileCompletionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(private readonly GuruProfileCompletionService $profileCompletionService)
    {
    }

    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();

        $onboardingStatus = $user->hasRole('guru')
            ? $this->profileCompletionService->onboardingStatus($user->loadMissing('guru.pasti'))
            : null;

        $wizardStep = 'profile';

        if ($onboardingStatus) {
            if (! $onboardingStatus['profile_completed']) {
                $wizardStep = 'profile';
            } elseif (! $onboardingStatus['pasti_completed']) {
                $wizardStep = 'pasti';
            } elseif ($onboardingStatus['password_change_required']) {
                $wizardStep = 'password';
            }

            $requestedStep = (string) $request->query('step', '');
            $allowedSteps = ['profile', 'pasti', 'password'];

            if (in_array($requestedStep, $allowedSteps, true)) {
                if ($requestedStep === 'profile') {
                    $wizardStep = 'profile';
                }

                if ($requestedStep === 'pasti' && $onboardingStatus['profile_completed']) {
                    $wizardStep = 'pasti';
                }

                if (
                    $requestedStep === 'password'
                    && $onboardingStatus['profile_completed']
                    && $onboardingStatus['pasti_completed']
                ) {
                    $wizardStep = 'password';
                }
            }
        }

        return view('profile.edit', [
            'user' => $user,
            'onboardingStatus' => $onboardingStatus,
            'wizardStep' => $wizardStep,
            'kawasans' => $user->hasRole('guru')
                ? Kawasan::query()->orderBy('name')->get(['id', 'name'])
                : collect(),
            'guruPasti' => $user->hasRole('guru') ? $user->guru?->pasti : null,
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
            $data['phone'],
            $data['marital_status'],
            $data['kursus_guru'],
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
                'phone' => $request->input('phone') ?: null,
                'marital_status' => $request->input('marital_status') ?: null,
                'kursus_guru' => $request->input('kursus_guru') ?: null,
                'joined_at' => $request->input('joined_at') ?: null,
            ]);

            $status = $this->profileCompletionService->onboardingStatus($user->fresh()->loadMissing('guru.pasti'));

            if ($status['profile_completed'] && ! $status['pasti_completed']) {
                return Redirect::route('profile.edit', ['step' => 'pasti'])
                    ->with('status_key', 'profile-updated')
                    ->with('status', __('messages.profile_updated'))
                    ->with('wizard_notice', 'Profil berjaya dikemaskini. Seterusnya, sila kemaskini maklumat PASTI.');
            }

            if ($status['profile_completed'] && $status['pasti_completed'] && $status['password_change_required']) {
                return Redirect::route('profile.edit', ['step' => 'password'])
                    ->with('status_key', 'profile-updated')
                    ->with('status', __('messages.profile_updated'))
                    ->with('wizard_notice', 'Profil berjaya dikemaskini. Seterusnya, sila tukar kata laluan anda.');
            }
        }

        return Redirect::route('profile.edit')
            ->with('status_key', 'profile-updated')
            ->with('status', __('messages.profile_updated'));
    }
}
