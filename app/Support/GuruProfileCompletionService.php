<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class GuruProfileCompletionService
{
    public const DEFAULT_GURU_PASSWORD = '123';

    /**
     * @return array<int, string>
     */
    public function missingFields(User $user): array
    {
        $guru = $user->guru;

        $missing = [];

        if (blank($user->nama_samaran)) {
            $missing[] = 'nama_samaran';
        }

        if (blank($user->tarikh_lahir)) {
            $missing[] = 'tarikh_lahir';
        }

        if (blank($guru?->pasti_id)) {
            $missing[] = 'pasti_id';
        }

        if (blank($guru?->phone)) {
            $missing[] = 'phone';
        }

        if (blank($guru?->marital_status)) {
            $missing[] = 'marital_status';
        }

        if (blank($user->avatar_path)) {
            $missing[] = 'avatar';
        }

        if (blank($guru?->joined_at)) {
            $missing[] = 'joined_at';
        }

        return $missing;
    }

    public function isCompleted(User $user): bool
    {
        return $this->missingFields($user) === [];
    }

    public function requiresPasswordChange(User $user): bool
    {
        if (blank($user->password)) {
            return true;
        }

        return Hash::check(self::DEFAULT_GURU_PASSWORD, (string) $user->password);
    }

    /**
     * @return array{
     *     profile_completed: bool,
     *     missing_fields: array<int, string>,
     *     password_change_required: bool,
     *     onboarding_completed: bool
     * }
     */
    public function onboardingStatus(User $user): array
    {
        $missingFields = $this->missingFields($user);
        $passwordChangeRequired = $this->requiresPasswordChange($user);

        return [
            'profile_completed' => $missingFields === [],
            'missing_fields' => $missingFields,
            'password_change_required' => $passwordChangeRequired,
            'onboarding_completed' => $missingFields === [] && ! $passwordChangeRequired,
        ];
    }
}
