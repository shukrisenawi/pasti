<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Pasti;
use App\Support\GuruProfileCompletionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
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
            'pastis' => $user->hasRole('guru')
                ? Pasti::query()->with('kawasan')->orderBy('name')->get(['id', 'kawasan_id', 'name'])
                : collect(),
            'guruPastiId' => $user->hasRole('guru') ? $user->guru?->pasti_id : null,
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
                'joined_at' => $request->input('joined_at') ?: null,
            ]);

            $status = $this->profileCompletionService->onboardingStatus($user->fresh()->loadMissing('guru.pasti'));

            if ($status['profile_completed'] && ! $status['pasti_completed']) {
                return Redirect::route('profile.edit', ['step' => 'pasti'])
                    ->with('status_key', 'profile-updated')
                    ->with('status', __('messages.profile_updated'))
                    ->with('wizard_notice', 'Profil berjaya dikemaskini. Seterusnya, sila pilih PASTI anda.');
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

    public function updatePastiSelection(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasRole('guru') && $user->guru, 403);

        $data = $request->validate([
            'pasti_id' => ['required', 'integer', Rule::exists('pastis', 'id')],
        ]);

        $user->guru->update([
            'pasti_id' => (int) $data['pasti_id'],
        ]);

        $status = $this->profileCompletionService->onboardingStatus($user->fresh()->loadMissing('guru.pasti'));

        if ($status['profile_completed'] && $status['pasti_completed'] && $status['password_change_required']) {
            return Redirect::route('profile.edit', ['step' => 'password'])
                ->with('status', __('messages.saved'))
                ->with('wizard_notice', 'PASTI berjaya dipilih. Seterusnya, sila tukar kata laluan anda.');
        }

        return Redirect::route('profile.edit', ['step' => 'pasti'])
            ->with('status', __('messages.saved'));
    }
}
