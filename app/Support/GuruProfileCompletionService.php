<?php

namespace App\Support;

use App\Models\User;

class GuruProfileCompletionService
{
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
}
