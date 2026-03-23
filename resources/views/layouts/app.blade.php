<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sistem pengurusan pasti</title>
    <link rel="icon" type="image/png" href="{{ asset('images/pasti-logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="m-0">
<div class="panel-shell relative overflow-hidden">
    @php
        $authUser = auth()->user();
        $isGuruOnly = $authUser->hasRole('guru') && ! $authUser->hasAnyRole(['master_admin', 'admin']);
    @endphp
    <div class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-72 bg-gradient-to-b from-primary/10 via-primary/5 to-transparent"></div>

    <header class="sticky top-0 z-30 border-b border-white/70 bg-white/80 backdrop-blur-xl">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="navbar">
                <div class="min-w-0 flex flex-1 items-center gap-3">
                    <a href="{{ route('dashboard') }}" class="shrink-0">
                        <x-application-logo class="h-12 w-12 rounded-full border border-primary/20 bg-white object-contain p-1 shadow-sm" />
                    </a>
                    <div class="min-w-0">
                        <a href="{{ route('dashboard') }}" class="text-xl font-extrabold tracking-tight text-primary">PASTI Portal</a>
                        <p class="text-xs text-slate-500 {{ $isGuruOnly ? 'hidden sm:block' : '' }}">{{ __('messages.portal_subtitle') }}</p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 sm:gap-3">
                    @php
                        $showNotifications = true;
                        $latestNotifications = $showNotifications
                            ? auth()->user()->unreadNotifications()->latest()->limit(5)->get()
                            : collect();
                        $unreadNotificationsCount = $showNotifications
                            ? auth()->user()->unreadNotifications()->count()
                            : 0;
                    @endphp

                    @if($showNotifications)
                        <div class="relative" x-data="{ open: false }" @click.outside="open = false">
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
                                class="absolute right-0 z-[1000] mt-3 w-[min(22rem,calc(100vw-1.5rem))] max-h-96 overflow-y-auto rounded-3xl border border-slate-200 bg-white p-2 shadow-2xl"
                                style="display: none;"
                            >
                                <p class="px-3 py-2 text-xs font-bold uppercase tracking-[0.18em] text-slate-500">{{ __('messages.notifications') }}</p>
                                @forelse($latestNotifications as $notification)
                                    <form method="POST" action="{{ route('notifications.read', $notification) }}" class="mt-1">
                                        @csrf
                                        <input type="hidden" name="redirect_to" value="{{ $notification->data['url'] ?? route('leave-notices.index') }}">
                                        @php
                                            $notificationAvatar = $notification->data['guru_avatar_url'] ?? '/images/default-avatar.svg';
                                            $notificationTitle = $notification->data['notification_title'] ?? __('messages.notifications');
                                            $notificationMeta = $notification->data['notification_meta'] ?? (($notification->data['guru_name'] ?? '-') . ' · ' . ($notification->data['pasti_name'] ?? '-'));
                                            $notificationMessage = \Illuminate\Support\Str::limit($notification->data['notification_message'] ?? ($notification->data['reason'] ?? '-'), 70);
                                        @endphp
                                        <button type="submit" class="w-full rounded-2xl px-3 py-3 text-left transition hover:bg-primary/5">
                                            <div class="flex items-start gap-3">
                                                <x-avatar
                                                    size="h-10 w-10"
                                                    rounded="rounded-xl"
                                                    :guru="\App\Models\Guru::where('name', $notification->data['guru_name'] ?? '')->first()"
                                                />
                                                <div class="min-w-0">
                                                    <p class="text-sm font-semibold text-slate-900">
                                                        {{ $notificationTitle }}
                                                    </p>
                                                    <p class="mt-1 text-xs text-slate-500">
                                                        {{ $notificationMeta }}
                                                    </p>
                                                    <p class="mt-2 text-xs leading-relaxed text-slate-600">
                                                        {{ $notificationMessage }}
                                                    </p>
                                                </div>
                                            </div>
                                        </button>
                                    </form>
                                @empty
                                    <p class="px-3 py-3 text-sm text-slate-500">{{ __('messages.no_notifications') }}</p>
                                @endforelse
                            </div>
                        </div>
                    @endif
                    <x-avatar :user="$authUser" size="h-10 w-10" rounded="rounded-2xl" border="border border-slate-200/50" class="{{ $isGuruOnly ? '' : 'hidden sm:block' }}" />

                    <a href="{{ route('profile.edit') }}" class="btn btn-outline btn-sm self-center {{ $isGuruOnly ? 'hidden sm:inline-flex' : '' }}">{{ __('messages.profile') }}</a>

                    <form method="POST" action="{{ route('logout') }}" class="m-0 items-center {{ $isGuruOnly ? 'hidden sm:flex' : 'flex' }}">
                        @csrf
                        <button class="btn btn-primary btn-sm">{{ __('messages.logout') }}</button>
                    </form>

                    @if($isGuruOnly)
                        <a href="{{ route('profile.edit') }}" class="btn btn-ghost btn-circle sm:hidden" aria-label="{{ __('messages.profile') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </a>
                        <form method="POST" action="{{ route('logout') }}" class="m-0 sm:hidden">
                            @csrf
                            <button type="submit" class="btn btn-ghost btn-circle" aria-label="{{ __('messages.logout') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </header>

    <div class="mx-auto max-w-7xl gap-6 px-4 py-6 sm:px-6 lg:px-8 {{ $isGuruOnly ? 'grid grid-cols-1' : 'grid lg:grid-cols-[280px_minmax(0,1fr)]' }}">
        <aside class="card order-2 h-fit overflow-hidden border-primary/10 bg-white/90 lg:order-1 {{ $isGuruOnly ? 'hidden' : '' }}">
            <div class="rounded-[1.6rem] bg-gradient-to-br from-primary via-primary-dark to-emerald-700 p-5 text-primary-content shadow-lg">
                <div class="flex items-center gap-4">
                    <x-avatar :user="$authUser" size="h-14 w-14" rounded="rounded-2xl" border="border border-white/20" />
                    <div class="min-w-0">
                        <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-white/70">User</p>
                        <p class="truncate text-base font-bold">{{ auth()->user()->display_name }}</p>
                        <p class="truncate text-sm text-white/75">{{ auth()->user()->email }}</p>
                    </div>
                </div>
            </div>

            <nav class="mt-5 space-y-1.5 text-sm">
                <a href="{{ route('dashboard') }}" class="menu-link {{ request()->routeIs('dashboard') ? 'menu-link-active' : '' }}">{{ __('messages.dashboard') }}</a>

                @role('master_admin')
                    <a href="{{ route('users.admins.index') }}" class="menu-link {{ request()->routeIs('users.admins.*') ? 'menu-link-active' : '' }}">{{ __('messages.admin_accounts') }}</a>
                @endrole

                @role('master_admin|admin')
                    <a href="{{ route('kawasan.index') }}" class="menu-link {{ request()->routeIs('kawasan.*') ? 'menu-link-active' : '' }}">{{ __('messages.kawasan') }}</a>
                    <a href="{{ route('users.gurus.index') }}" class="menu-link {{ request()->routeIs('users.gurus.*') ? 'menu-link-active' : '' }}">{{ __('messages.guru') }}</a>
                    <a href="{{ route('pasti.index') }}" class="menu-link {{ request()->routeIs('pasti.*') ? 'menu-link-active' : '' }}">{{ __('messages.pasti') }}</a>
                    <a href="{{ route('ajk-program.index') }}" class="menu-link {{ request()->routeIs('ajk-program.*') ? 'menu-link-active' : '' }}">{{ __('messages.ajk_program') }}</a>
                    <a href="{{ route('kpi.gurus.index') }}" class="menu-link {{ request()->routeIs('kpi.gurus.*') ? 'menu-link-active' : '' }}">{{ __('messages.kpi_guru') }}</a>
                    
                    @php
                        $expiredSkimPasCount = \App\Models\User::where('tarikh_exp_skim_pas', '<', now()->startOfDay())->count();
                    @endphp
                    <a href="{{ route('users.expired-skim-pas') }}" class="menu-link {{ request()->routeIs('users.expired-skim-pas') ? 'menu-link-active' : '' }} flex items-center justify-between">
                        <span>{{ __('messages.skim_pas_expired_list') }}</span>
                        @if($expiredSkimPasCount > 0)
                            <span class="rounded-full bg-emerald-600 px-2 py-0.5 text-[10px] font-bold text-white shadow-sm" style="background-color: #059669 !important;">{{ $expiredSkimPasCount }}</span>
                        @endif
                    </a>
                @endrole
                @role('guru')
                    <a href="{{ route('pasti.self.edit') }}" class="menu-link {{ request()->routeIs('pasti.self.*') ? 'menu-link-active' : '' }}">{{ __('messages.pasti') }}</a>
                @endrole

                <a href="{{ route('pemarkahan.index') }}" class="menu-link {{ request()->routeIs('pemarkahan.*') ? 'menu-link-active' : '' }}">{{ __('messages.pemarkahan') }}</a>
                <a href="{{ route('pasti-information.index') }}" class="menu-link {{ request()->routeIs('pasti-information.*') ? 'menu-link-active' : '' }}">{{ __('messages.maklumat_pasti') }}</a>
                <a href="{{ route('programs.index') }}" class="menu-link {{ request()->routeIs('programs.*') ? 'menu-link-active' : '' }}">{{ __('messages.programs') }}</a>
                <a href="{{ route('messages.index') }}" class="menu-link {{ request()->routeIs('messages.*') ? 'menu-link-active' : '' }}">{{ __('messages.inbox') }}</a>
                <a href="{{ route('leave-notices.index') }}" class="menu-link {{ request()->routeIs('leave-notices.*') ? 'menu-link-active' : '' }}">{{ __('messages.leave_notice') }}</a>

                @role('guru')
                    @if(auth()->user()->guru)
                        <a href="{{ route('kpi.guru.show', auth()->user()->guru) }}" class="menu-link {{ request()->routeIs('kpi.guru.show') ? 'menu-link-active' : '' }}">{{ __('messages.my_kpi') }}</a>
                    @endif
                @endrole
            </nav>
        </aside>

        <main class="order-1 space-y-4 lg:order-2 {{ $isGuruOnly ? 'guru-main-with-bottom-nav' : '' }}">
            @isset($header)
                <div class="card border-primary/10 bg-white/95">
                    {{ $header }}
                </div>
            @endisset

            @if (session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-error">
                    <div>
                        <p class="mb-1 text-sm font-semibold">{{ __('messages.validation_failed') }}</p>
                        <ul class="list-disc pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <div>{{ $slot }}</div>
        </main>
    </div>

    <x-bottom-nav />
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        flatpickr('input[type="date"]', {
            altInput: true,
            altFormat: "d/m/Y",
            dateFormat: "Y-m-d",
            allowInput: true,
            onReady: function(selectedDates, dateStr, instance) {
                if (instance.altInput) {
                    instance.altInput.classList.add('input-base');
                    // Copy other classes if needed
                    const originalClasses = instance.element.classList;
                    originalClasses.forEach(cls => {
                        if (cls !== 'flatpickr-input') {
                            instance.altInput.classList.add(cls);
                        }
                    });
                }
            }
        });
    });
</script>
</body>
</html>





