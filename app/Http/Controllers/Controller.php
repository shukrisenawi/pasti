<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;

abstract class Controller
{
    use AuthorizesRequests, ValidatesRequests;

    protected function isMasterAdmin(User $user): bool
    {
        return $user->hasRole('master_admin');
    }

    protected function isAdmin(User $user): bool
    {
        return $user->hasRole('admin');
    }

    protected function assignedPastiIds(User $user): array
    {
        if ($this->isMasterAdmin($user)) {
            return [];
        }

        return $user->assignedPastis()->pluck('pastis.id')->all();
    }
}
