<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Component;

class NavbarNotifications extends Component
{
    public function render(): View
    {
        $user = auth()->user();

        /** @var Collection<int, \Illuminate\Notifications\DatabaseNotification> $latestNotifications */
        $latestNotifications = $user
            ? $user->unreadNotifications()->latest()->limit(5)->get()
            : collect();

        return view('livewire.navbar-notifications', [
            'latestNotifications' => $latestNotifications,
            'unreadNotificationsCount' => $user ? $user->unreadNotifications()->count() : 0,
        ]);
    }
}
