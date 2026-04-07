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
        <div class="flex items-center justify-between px-2 py-1.5">
            <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-slate-500">{{ __('messages.notifications') }}</p>
            @if($unreadNotificationsCount > 0)
                <form method="POST" action="{{ route('notifications.destroy-all') }}">
                    @csrf
                    @method('DELETE')
                    <button
                        type="submit"
                        class="text-xs font-semibold text-rose-600 transition hover:text-rose-700"
                        onclick="return confirm('Padam semua notifikasi?')"
                    >
                        Padam semua notifikasi
                    </button>
                </form>
            @endif
        </div>
        @forelse($latestNotifications as $notification)
            @php
                $notificationAvatar = $notification->data['guru_avatar_url'] ?? '/images/default-avatar.svg';
                $notificationTitle = $notification->data['notification_title'] ?? __('messages.notifications');
                $notificationMeta = $notification->data['notification_meta'] ?? (($notification->data['guru_name'] ?? '-') . ' - ' . ($notification->data['pasti_name'] ?? '-'));
                $notificationMessage = \Illuminate\Support\Str::limit($notification->data['notification_message'] ?? ($notification->data['reason'] ?? '-'), 70);
            @endphp

            <div class="mt-1 rounded-xl border border-slate-100 transition hover:bg-primary/5">
                <div class="flex items-start gap-2 p-2">
                    <form method="POST" action="{{ route('notifications.read', $notification) }}" class="min-w-0 flex-1">
                        @csrf
                        <input type="hidden" name="redirect_to" value="{{ $notification->data['url'] ?? route('leave-notices.index') }}">
                        <button type="submit" class="w-full text-left">
                            <div class="flex items-start gap-2.5">
                                <img src="{{ $notificationAvatar }}" alt="avatar" class="h-8 w-8 rounded-lg border border-slate-200 object-cover">
                                <div class="min-w-0">
                                    <p class="truncate text-[13px] font-semibold leading-tight text-slate-900">{{ $notificationTitle }}</p>
                                    <p class="mt-0.5 truncate text-[11px] text-slate-500">{{ $notificationMeta }}</p>
                                    <p class="mt-1 text-[11px] leading-snug text-slate-600">{{ $notificationMessage }}</p>
                                </div>
                            </div>
                        </button>
                    </form>

                    <form method="POST" action="{{ route('notifications.destroy', $notification) }}" class="shrink-0">
                        @csrf
                        @method('DELETE')
                        <button
                            type="submit"
                            class="inline-flex h-7 w-7 items-center justify-center rounded-md text-slate-400 transition hover:bg-rose-50 hover:text-rose-600"
                            onclick="return confirm('Padam notifikasi ini?')"
                            aria-label="Padam notifikasi"
                            title="Padam notifikasi"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7l.6 11.2A2 2 0 0010.6 20h2.8a2 2 0 002-1.8L16 7" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 11v5M14 11v5" />
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <p class="px-3 py-3 text-sm text-slate-500">{{ __('messages.no_notifications') }}</p>
        @endforelse
    </div>
</div>
