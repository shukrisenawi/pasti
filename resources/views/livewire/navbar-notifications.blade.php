<div class="relative" x-data="{ open: false }" @click.outside="open = false" wire:poll.10s>
    <button type="button" @click="open = !open" class="btn btn-ghost btn-circle relative" aria-label="{{ __('messages.notifications') }}">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>
        @if($unreadNotificationsCount > 0)
            <span class="badge badge-primary badge-xs absolute -right-1 -top-1">{{ $unreadNotificationsCount > 99 ? '99+' : $unreadNotificationsCount }}</span>
        @endif
    </button>

    <div
        x-show="open"
        x-transition.origin.top.right
        class="absolute left-1/2 z-[1000] mt-3 max-h-96 w-[calc(100vw-1rem)] -translate-x-1/2 overflow-y-auto rounded-3xl border border-slate-200 bg-white p-2 shadow-2xl sm:left-auto sm:right-0 sm:w-[22rem] sm:translate-x-0"
        style="display: none;"
    >
        <p class="px-3 py-2 text-xs font-bold uppercase tracking-[0.18em] text-slate-500">{{ __('messages.notifications') }}</p>
        @forelse($latestNotifications as $notification)
            @php
                $notificationAvatar = $notification->data['guru_avatar_url'] ?? '/images/default-avatar.svg';
                $notificationTitle = $notification->data['notification_title'] ?? __('messages.notifications');
                $notificationMeta = $notification->data['notification_meta'] ?? (($notification->data['guru_name'] ?? '-') . ' - ' . ($notification->data['pasti_name'] ?? '-'));
                $notificationMessage = \Illuminate\Support\Str::limit($notification->data['notification_message'] ?? ($notification->data['reason'] ?? '-'), 70);
            @endphp

            <div class="mt-1 rounded-2xl transition hover:bg-primary/5">
                <form method="POST" action="{{ route('notifications.read', $notification) }}">
                    @csrf
                    <input type="hidden" name="redirect_to" value="{{ $notification->data['url'] ?? route('leave-notices.index') }}">
                    <button type="submit" class="w-full px-3 pt-3 text-left">
                        <div class="flex items-start gap-3">
                            <img src="{{ $notificationAvatar }}" alt="avatar" class="h-10 w-10 rounded-xl border border-slate-200 object-cover">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-slate-900">{{ $notificationTitle }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $notificationMeta }}</p>
                                <p class="mt-2 text-xs leading-relaxed text-slate-600">{{ $notificationMessage }}</p>
                            </div>
                        </div>
                    </button>
                </form>

                <div class="flex justify-end px-3 pb-3">
                    <form method="POST" action="{{ route('notifications.destroy', $notification) }}">
                        @csrf
                        @method('DELETE')
                        <button
                            type="submit"
                            class="btn btn-ghost btn-xs text-rose-600 hover:bg-rose-50 hover:text-rose-700"
                            onclick="return confirm('Padam notifikasi ini?')"
                        >
                            Padam
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <p class="px-3 py-3 text-sm text-slate-500">{{ __('messages.no_notifications') }}</p>
        @endforelse
    </div>
</div>
