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
    @livewireStyles
</head>
<body class="m-0">
<div class="panel-shell relative overflow-hidden" x-data="{ mobileMenuOpen: false }">
    @php
        $authUser = auth()->user();
        $isGuruOnly = $authUser->hasRole('guru') && ! $authUser->hasAnyRole(['master_admin', 'admin']);
        $pastiMenuRoute = $authUser->hasRole('guru') ? route('pasti.self.edit') : null;
    @endphp
    <div class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-72 bg-gradient-to-b from-primary/10 via-primary/5 to-transparent"></div>

    {{-- Mobile Drawer Backdrop --}}
    <div
        x-show="mobileMenuOpen"
        x-transition:enter="transition-opacity ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="mobileMenuOpen = false"
        class="fixed inset-0 z-[200] bg-black/40 backdrop-blur-sm lg:hidden"
        style="display: none;"
    ></div>

    {{-- Mobile Slide-in Drawer --}}
    <div
        x-show="mobileMenuOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="-translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="-translate-x-full"
        class="fixed inset-y-0 left-0 z-[300] w-72 overflow-y-auto bg-white shadow-2xl lg:hidden"
        style="display: none;"
    >
        {{-- Drawer Header --}}
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
            <div class="flex items-center gap-3">
                <x-application-logo class="h-10 w-10 rounded-full border border-primary/20 bg-white object-contain p-1 shadow-sm" />
                <span class="text-base font-extrabold tracking-tight text-primary">PASTI Portal</span>
            </div>
            <button @click="mobileMenuOpen = false" class="btn btn-ghost btn-sm h-8 w-8 rounded-xl p-0" aria-label="Tutup menu">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        {{-- Drawer User Info --}}
        <div class="m-3 rounded-2xl bg-gradient-to-br from-primary via-primary-dark to-emerald-700 p-4 text-primary-content shadow">
            <div class="flex items-center gap-3">
                <x-avatar :user="$authUser" size="h-11 w-11" rounded="rounded-xl" border="border border-white/20" />
                <div class="min-w-0">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-white/70">User</p>
                    <p class="truncate text-sm font-bold">{{ auth()->user()->display_name }}</p>
                    <p class="truncate text-xs text-white/75">{{ auth()->user()->email }}</p>
                </div>
            </div>
        </div>

        {{-- Drawer Navigation --}}
        <nav class="space-y-1 px-3 py-2 text-sm">
            @php
                $drawerInboxCount = $authUser->unreadNotifications()->where('type', 'like', '%Message%')->count();
                $drawerUpcomingProgramCount = \App\Models\Program::query()
                    ->when($isGuruOnly, fn ($q) => $q->whereHas('gurus', fn ($q2) => $q2->where('gurus.id', $authUser->guru?->id ?? 0)))
                    ->whereDate('program_date', '>=', now()->toDateString())
                    ->count();
                $drawerPastiInfoPendingCount = \App\Models\PastiInformationRequest::query()
                    ->when($authUser->hasRole('guru'), fn ($q) => $q->where('pasti_id', $authUser->guru?->pasti_id ?? 0))
                    ->when($authUser->hasRole('admin') && !$authUser->hasRole('master_admin'), fn ($q) => $q->whereIn('pasti_id', $authUser->assignedPastis()->pluck('pastis.id')))
                    ->whereNull('completed_at')
                    ->count();
                $drawerOnLeaveGuruCount = \App\Models\LeaveNotice::query()
                    ->when($authUser->hasRole('guru'), fn ($q) => $q->where('guru_id', $authUser->guru?->id ?? 0))
                    ->when($authUser->hasRole('admin') && ! $authUser->hasRole('master_admin'), fn ($q) => $q->whereHas('guru', fn ($q2) => $q2->whereIn('pasti_id', $authUser->assignedPastis()->pluck('pastis.id'))))
                    ->whereDate('leave_date', '<=', now()->toDateString())
                    ->whereDate('leave_until', '>=', now()->toDateString())
                    ->distinct('guru_id')
                    ->count('guru_id');
                $drawerPendingClaimsCount = $authUser->pending_claims_count;
            @endphp

            <a href="{{ route('dashboard') }}" wire:navigate @click="mobileMenuOpen = false" class="menu-link {{ request()->routeIs('dashboard') ? 'menu-link-active' : '' }}">{{ __('messages.dashboard') }}</a>

            @role('master_admin|admin')
                {{-- Pengurusan Dropdown --}}
                <div x-data="{ open: {{ request()->routeIs(['kawasan.*', 'pasti.*', 'users.gurus.*', 'users.admins.*', 'ajk-program.*']) ? 'true' : 'false' }} }" class="space-y-1">
                    <button @click="open = !open" class="menu-link w-full flex items-center justify-between {{ request()->routeIs(['kawasan.*', 'pasti.*', 'users.gurus.*', 'users.admins.*', 'ajk-program.*']) ? 'text-primary bg-primary/5' : '' }}">
                        <div class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" /></svg>
                            <span>{{ __('Pengurusan') }}</span>
                        </div>
                        <svg class="h-4 w-4 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                    </button>
                    <div x-show="open" x-cloak x-collapse class="pl-4 space-y-1 border-l-2 border-primary/10 ml-4">
                        <a href="{{ route('kawasan.index') }}" wire:navigate @click="mobileMenuOpen = false" class="menu-link !py-2 !px-3 {{ request()->routeIs('kawasan.*') ? 'menu-link-active' : '' }}">{{ __('messages.kawasan') }}</a>
                        <a href="{{ route('pasti.index') }}" wire:navigate @click="mobileMenuOpen = false" class="menu-link !py-2 !px-3 {{ request()->routeIs('pasti.*') ? 'menu-link-active' : '' }}">{{ __('messages.pasti') }}</a>
                        <a href="{{ route('users.gurus.index') }}" wire:navigate @click="mobileMenuOpen = false" class="menu-link !py-2 !px-3 {{ request()->routeIs('users.gurus.*') ? 'menu-link-active' : '' }}">{{ __('messages.guru') }}</a>
                        @role('master_admin')
                            <a href="{{ route('users.admins.index') }}" wire:navigate @click="mobileMenuOpen = false" class="menu-link !py-2 !px-3 {{ request()->routeIs('users.admins.*') ? 'menu-link-active' : '' }}">{{ __('messages.admin_accounts') }}</a>
                        @endrole
                        <a href="{{ route('ajk-program.index') }}" wire:navigate @click="mobileMenuOpen = false" class="menu-link !py-2 !px-3 {{ request()->routeIs('ajk-program.*') ? 'menu-link-active' : '' }}">{{ __('messages.ajk_program') }}</a>
                    </div>
                </div>

                {{-- Laporan/Aktiviti Dropdown --}}
                <div x-data="{ open: {{ request()->routeIs(['financial.*', 'kpi.gurus.*', 'users.expired-skim-pas', 'pemarkahan.*', 'pasti-information.*', 'programs.*', 'messages.*', 'leave-notices.*']) ? 'true' : 'false' }} }" class="space-y-1">
                    <button @click="open = !open" class="menu-link w-full flex items-center justify-between {{ request()->routeIs(['financial.*', 'kpi.gurus.*', 'users.expired-skim-pas', 'pemarkahan.*', 'pasti-information.*', 'programs.*', 'messages.*', 'leave-notices.*']) ? 'text-primary bg-primary/5' : '' }}">
                        <div class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 2v-6m-8-2h12a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h4l2 2z" /></svg>
                            <span>{{ __('Laporan/Aktiviti') }}</span>
                        </div>
                        <svg class="h-4 w-4 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                    </button>
                    <div x-show="open" x-cloak x-collapse class="pl-4 space-y-1 border-l-2 border-primary/10 ml-4">
                        <a href="{{ route('financial.index') }}" wire:navigate @click="mobileMenuOpen = false" class="menu-link !py-2 !px-3 {{ request()->routeIs('financial.*') ? 'menu-link-active' : '' }}">{{ __('messages.kewangan') }}</a>
                        <a href="{{ route('kpi.gurus.index') }}" wire:navigate @click="mobileMenuOpen = false" class="menu-link !py-2 !px-3 {{ request()->routeIs('kpi.gurus.*') ? 'menu-link-active' : '' }}">{{ __('messages.kpi_guru') }}</a>
                        @php $expiredSkimPasCount = \App\Models\User::where('tarikh_exp_skim_pas', '<', now()->startOfDay())->count(); @endphp
                        <a href="{{ route('users.expired-skim-pas') }}" wire:navigate @click="mobileMenuOpen = false" class="menu-link !py-2 !px-3 {{ request()->routeIs('users.expired-skim-pas') ? 'menu-link-active' : '' }} flex items-center justify-between gap-1">
                            <span class="truncate">{{ __('messages.skim_pas_expired_list') }}</span>
                            @if($expiredSkimPasCount > 0)<span class="rounded-full bg-emerald-600 px-2 py-0.5 text-[10px] font-bold text-white shrink-0">{{ $expiredSkimPasCount }}</span>@endif
                        </a>
                        <a href="{{ route('pemarkahan.index') }}" wire:navigate @click="mobileMenuOpen = false" class="menu-link !py-2 !px-3 {{ request()->routeIs('pemarkahan.*') ? 'menu-link-active' : '' }}">{{ __('messages.pemarkahan') }}</a>
                        <a href="{{ route('pasti-information.index') }}" wire:navigate @click="mobileMenuOpen = false" class="menu-link !py-2 !px-3 {{ request()->routeIs('pasti-information.*') ? 'menu-link-active' : '' }} flex items-center justify-between gap-1">
                            <span>{{ __('messages.maklumat_pasti') }}</span>
                            @if($drawerPastiInfoPendingCount > 0)<span class="rounded-full bg-amber-500 px-2 py-0.5 text-[10px] font-bold text-white shrink-0">{{ $drawerPastiInfoPendingCount > 99 ? '99+' : $drawerPastiInfoPendingCount }}</span>@endif
                        </a>
                        <a href="{{ route('programs.index') }}" wire:navigate @click="mobileMenuOpen = false" class="menu-link !py-2 !px-3 {{ request()->routeIs('programs.*') ? 'menu-link-active' : '' }} flex items-center justify-between gap-1">
                            <span>{{ __('messages.programs') }}</span>
                            @if($drawerUpcomingProgramCount > 0)<span class="rounded-full bg-primary px-2 py-0.5 text-[10px] font-bold text-white shrink-0">{{ $drawerUpcomingProgramCount > 99 ? '99+' : $drawerUpcomingProgramCount }}</span>@endif
                        </a>
                        <a href="{{ route('messages.index') }}" wire:navigate @click="mobileMenuOpen = false" class="menu-link !py-2 !px-3 {{ request()->routeIs('messages.*') ? 'menu-link-active' : '' }} flex items-center justify-between gap-1">
                            <span>{{ __('messages.inbox') }}</span>
                            @if($drawerInboxCount > 0)<span class="rounded-full bg-rose-500 px-2 py-0.5 text-[10px] font-bold text-white shrink-0">{{ $drawerInboxCount > 99 ? '99+' : $drawerInboxCount }}</span>@endif
                        </a>
                        <a href="{{ route('leave-notices.index') }}" wire:navigate @click="mobileMenuOpen = false" class="menu-link !py-2 !px-3 {{ request()->routeIs('leave-notices.*') ? 'menu-link-active' : '' }} flex items-center justify-between gap-1">
                            <span>{{ __('messages.leave_notice') }}</span>
                            @if($drawerOnLeaveGuruCount > 0)<span class="rounded-full bg-indigo-600 px-2 py-0.5 text-[10px] font-bold text-white shrink-0">{{ $drawerOnLeaveGuruCount > 99 ? '99+' : $drawerOnLeaveGuruCount }}</span>@endif
                        </a>
                    </div>
                </div>
            @endrole

            <a href="{{ route('claims.index') }}" wire:navigate @click="mobileMenuOpen = false" class="menu-link {{ request()->routeIs('claims.*') ? 'menu-link-active' : '' }} flex items-center justify-between gap-1">
                <span>{{ __('messages.claim') }}</span>
                @if($drawerPendingClaimsCount > 0)<span class="rounded-full bg-emerald-600 px-2 py-0.5 text-[10px] font-bold text-white shrink-0" style="background-color: #059669 !important;">{{ $drawerPendingClaimsCount > 99 ? '99+' : $drawerPendingClaimsCount }}</span>@endif
            </a>

            @role('guru')
                @if(!auth()->user()->hasAnyRole(['master_admin', 'admin']))
                    <a href="{{ route('pemarkahan.index') }}" wire:navigate @click="mobileMenuOpen = false" class="menu-link {{ request()->routeIs('pemarkahan.*') ? 'menu-link-active' : '' }}">{{ __('messages.pemarkahan') }}</a>
                    <a href="{{ route('pasti-information.index') }}" wire:navigate @click="mobileMenuOpen = false" class="menu-link {{ request()->routeIs('pasti-information.*') ? 'menu-link-active' : '' }} flex items-center justify-between">
                        <span>{{ __('messages.maklumat_pasti') }}</span>
                        @if($drawerPastiInfoPendingCount > 0)<span class="rounded-full bg-amber-500 px-2 py-0.5 text-[10px] font-bold text-white">{{ $drawerPastiInfoPendingCount > 99 ? '99+' : $drawerPastiInfoPendingCount }}</span>@endif
                    </a>
                    <a href="{{ route('programs.index') }}" wire:navigate @click="mobileMenuOpen = false" class="menu-link {{ request()->routeIs('programs.*') ? 'menu-link-active' : '' }} flex items-center justify-between">
                        <span>{{ __('messages.programs') }}</span>
                        @if($drawerUpcomingProgramCount > 0)<span class="rounded-full bg-primary px-2 py-0.5 text-[10px] font-bold text-white">{{ $drawerUpcomingProgramCount > 99 ? '99+' : $drawerUpcomingProgramCount }}</span>@endif
                    </a>
                    <a href="{{ route('messages.index') }}" wire:navigate @click="mobileMenuOpen = false" class="menu-link {{ request()->routeIs('messages.*') ? 'menu-link-active' : '' }} flex items-center justify-between">
                        <span>{{ __('messages.inbox') }}</span>
                        @if($drawerInboxCount > 0)<span class="rounded-full bg-rose-500 px-2 py-0.5 text-[10px] font-bold text-white">{{ $drawerInboxCount > 99 ? '99+' : $drawerInboxCount }}</span>@endif
                    </a>
                    <a href="{{ route('leave-notices.index') }}" wire:navigate @click="mobileMenuOpen = false" class="menu-link {{ request()->routeIs('leave-notices.*') ? 'menu-link-active' : '' }} flex items-center justify-between gap-1">
                        <span>{{ __('messages.leave_notice') }}</span>
                        @if($drawerOnLeaveGuruCount > 0)<span class="rounded-full bg-indigo-600 px-2 py-0.5 text-[10px] font-bold text-white shrink-0">{{ $drawerOnLeaveGuruCount > 99 ? '99+' : $drawerOnLeaveGuruCount }}</span>@endif
                    </a>
                    @if(auth()->user()->guru)
                        <a href="{{ route('kpi.guru.show', auth()->user()->guru) }}" wire:navigate @click="mobileMenuOpen = false" class="menu-link {{ request()->routeIs('kpi.guru.show') ? 'menu-link-active' : '' }}">{{ __('messages.my_kpi') }}</a>
                    @endif
                @endif
            @endrole
        </nav>

        {{-- Drawer Footer --}}
        <div class="border-t border-slate-100 p-3">
            <a href="{{ route('profile.edit') }}" wire:navigate @click="mobileMenuOpen = false" class="menu-link flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                {{ __('messages.profile') }}
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="menu-link w-full flex items-center gap-2 text-rose-600 hover:bg-rose-50 hover:text-rose-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                    {{ __('messages.logout') }}
                </button>
            </form>
        </div>
    </div>

    <header class="sticky top-0 z-30 border-b border-white/70 bg-white/80 backdrop-blur-xl">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="navbar">
                {{-- Hamburger Button (Mobile only, left side) --}}
                <button
                    @click="mobileMenuOpen = true"
                    class="-ml-1 mr-2 flex items-center justify-center rounded-xl p-2 text-slate-600 transition hover:bg-primary/10 hover:text-primary lg:hidden"
                    aria-label="Buka menu"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <div class="min-w-0 flex flex-1 items-center gap-3">
                    <a href="{{ route('dashboard') }}" wire:navigate class="shrink-0">
                        <x-application-logo class="h-12 w-12 rounded-full border border-primary/20 bg-white object-contain p-1 shadow-sm" />
                    </a>
                    <div class="min-w-0">
                        <a href="{{ route('dashboard') }}" wire:navigate class="text-xl font-extrabold tracking-tight text-primary">PASTI Portal</a>
                        <p class="text-xs text-slate-500 {{ $isGuruOnly ? 'hidden sm:block' : '' }}">{{ __('messages.portal_subtitle') }}</p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 sm:gap-3">
                    <livewire:navbar-notifications />
                    <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                        <button type="button" @click="open = !open" class="flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-2 py-1.5 shadow-sm transition hover:border-slate-300" aria-label="Menu pengguna">
                            <x-avatar :user="$authUser" size="h-9 w-9" rounded="rounded-xl" border="border border-slate-200/50" />
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6" />
                            </svg>
                        </button>

                        <div
                            x-show="open"
                            x-transition.origin.top.right
                            class="absolute right-0 z-[1000] mt-3 w-48 overflow-hidden rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl"
                            style="display: none;"
                        >
                            <a href="{{ route('profile.edit') }}" wire:navigate class="block rounded-xl px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                                {{ __('messages.profile') }}
                            </a>
                            @if($pastiMenuRoute)
                                <a href="{{ $pastiMenuRoute }}" wire:navigate class="mt-1 block rounded-xl px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                                    {{ __('messages.pasti') }}
                                </a>
                            @endif
                            <form method="POST" action="{{ route('logout') }}" class="mt-1">
                                @csrf
                                <button type="submit" class="block w-full rounded-xl px-3 py-2 text-left text-sm font-semibold text-rose-600 transition hover:bg-rose-50">
                                    {{ __('messages.logout') }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="mx-auto grid max-w-7xl gap-6 px-4 py-6 sm:px-6 lg:grid-cols-[280px_minmax(0,1fr)] lg:px-8">
        <aside class="card desktop-sidebar h-fit overflow-hidden border-primary/10 bg-white/90">
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
                @php
                    $menuInboxCount = $authUser->unreadNotifications()->where('type', 'like', '%Message%')->count();

                    $menuUpcomingProgramCount = \App\Models\Program::query()
                        ->when(
                            $isGuruOnly,
                            fn ($query) => $query->whereHas('gurus', fn ($q) => $q->where('gurus.id', $authUser->guru?->id ?? 0))
                        )
                        ->whereDate('program_date', '>=', now()->toDateString())
                        ->count();

                    $menuPastiInfoPendingCount = \App\Models\PastiInformationRequest::query()
                        ->when(
                            $authUser->hasRole('guru'),
                            fn ($query) => $query->where('pasti_id', $authUser->guru?->pasti_id ?? 0)
                        )
                        ->when(
                            $authUser->hasRole('admin') && ! $authUser->hasRole('master_admin'),
                            fn ($query) => $query->whereIn('pasti_id', $authUser->assignedPastis()->pluck('pastis.id'))
                        )
                        ->whereNull('completed_at')
                        ->count();

                    $menuOnLeaveGuruCount = \App\Models\LeaveNotice::query()
                        ->when(
                            $authUser->hasRole('guru'),
                            fn ($query) => $query->where('guru_id', $authUser->guru?->id ?? 0)
                        )
                        ->when(
                            $authUser->hasRole('admin') && ! $authUser->hasRole('master_admin'),
                            fn ($query) => $query->whereHas('guru', fn ($q) => $q->whereIn('pasti_id', $authUser->assignedPastis()->pluck('pastis.id')))
                        )
                        ->whereDate('leave_date', '<=', now()->toDateString())
                        ->whereDate('leave_until', '>=', now()->toDateString())
                        ->distinct('guru_id')
                        ->count('guru_id');

                    $menuPendingClaimsCount = $authUser->pending_claims_count;
                @endphp
                <a href="{{ route('dashboard') }}" wire:navigate class="menu-link {{ request()->routeIs('dashboard') ? 'menu-link-active' : '' }}">{{ __('messages.dashboard') }}</a>
                
                @role('master_admin|admin')
                    <!-- Group: Pengurusan -->
                    <div x-data="{ open: {{ request()->routeIs(['kawasan.*', 'pasti.*', 'users.gurus.*', 'users.admins.*', 'ajk-program.*']) ? 'true' : 'false' }} }" class="space-y-1">
                        <button @click="open = !open" class="menu-link w-full flex items-center justify-between {{ request()->routeIs(['kawasan.*', 'pasti.*', 'users.gurus.*', 'users.admins.*', 'ajk-program.*']) ? 'text-primary bg-primary/5' : '' }}">
                            <div class="flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" /></svg>
                                <span>{{ __('Pengurusan') }}</span>
                            </div>
                            <svg class="h-4 w-4 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                        </button>
                        <div x-show="open" x-cloak x-collapse class="pl-4 space-y-1 border-l-2 border-primary/10 ml-4">
                            <a href="{{ route('kawasan.index') }}" wire:navigate class="menu-link !py-2 !px-3 {{ request()->routeIs('kawasan.*') ? 'menu-link-active' : '' }}">{{ __('messages.kawasan') }}</a>
                            <a href="{{ route('pasti.index') }}" wire:navigate class="menu-link !py-2 !px-3 {{ request()->routeIs('pasti.*') ? 'menu-link-active' : '' }}">{{ __('messages.pasti') }}</a>
                            <a href="{{ route('users.gurus.index') }}" wire:navigate class="menu-link !py-2 !px-3 {{ request()->routeIs('users.gurus.*') ? 'menu-link-active' : '' }}">{{ __('messages.guru') }}</a>
                            @role('master_admin')
                                <a href="{{ route('users.admins.index') }}" wire:navigate class="menu-link !py-2 !px-3 {{ request()->routeIs('users.admins.*') ? 'menu-link-active' : '' }}">{{ __('messages.admin_accounts') }}</a>
                            @endrole
                            <a href="{{ route('ajk-program.index') }}" wire:navigate class="menu-link !py-2 !px-3 {{ request()->routeIs('ajk-program.*') ? 'menu-link-active' : '' }}">{{ __('messages.ajk_program') }}</a>
                        </div>
                    </div>

                    <!-- Group: Laporan/Aktiviti -->
                    <div x-data="{ open: {{ request()->routeIs(['financial.*', 'kpi.gurus.*', 'users.expired-skim-pas', 'pemarkahan.*', 'pasti-information.*', 'programs.*', 'messages.*', 'leave-notices.*']) ? 'true' : 'false' }} }" class="space-y-1">
                        <button @click="open = !open" class="menu-link w-full flex items-center justify-between {{ request()->routeIs(['financial.*', 'kpi.gurus.*', 'users.expired-skim-pas', 'pemarkahan.*', 'pasti-information.*', 'programs.*', 'messages.*', 'leave-notices.*']) ? 'text-primary bg-primary/5' : '' }}">
                            <div class="flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 2v-6m-8-2h12a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h4l2 2z" /></svg>
                                <span>{{ __('Laporan/Aktiviti') }}</span>
                            </div>
                            <svg class="h-4 w-4 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                        </button>
                        <div x-show="open" x-cloak x-collapse class="pl-4 space-y-1 border-l-2 border-primary/10 ml-4">
                            <a href="{{ route('financial.index') }}" wire:navigate class="menu-link !py-2 !px-3 {{ request()->routeIs('financial.*') ? 'menu-link-active' : '' }}">{{ __('messages.kewangan') }}</a>
                            <a href="{{ route('kpi.gurus.index') }}" wire:navigate class="menu-link !py-2 !px-3 {{ request()->routeIs('kpi.gurus.*') ? 'menu-link-active' : '' }}">{{ __('messages.kpi_guru') }}</a>
                            
                            @php
                                $expiredSkimPasCount = \App\Models\User::where('tarikh_exp_skim_pas', '<', now()->startOfDay())->count();
                            @endphp
                            <a href="{{ route('users.expired-skim-pas') }}" wire:navigate class="menu-link !py-2 !px-3 {{ request()->routeIs('users.expired-skim-pas') ? 'menu-link-active' : '' }} flex items-center justify-between gap-1">
                                <span class="truncate">{{ __('messages.skim_pas_expired_list') }}</span>
                                @if($expiredSkimPasCount > 0)
                                    <span class="rounded-full bg-emerald-600 px-2 py-0.5 text-[10px] font-bold text-white shrink-0" style="background-color: #059669 !important;">{{ $expiredSkimPasCount }}</span>
                                @endif
                            </a>

                            <a href="{{ route('pemarkahan.index') }}" wire:navigate class="menu-link !py-2 !px-3 {{ request()->routeIs('pemarkahan.*') ? 'menu-link-active' : '' }}">{{ __('messages.pemarkahan') }}</a>
                            
                            <a href="{{ route('pasti-information.index') }}" wire:navigate class="menu-link !py-2 !px-3 {{ request()->routeIs('pasti-information.*') ? 'menu-link-active' : '' }} flex items-center justify-between gap-1">
                                <span>{{ __('messages.maklumat_pasti') }}</span>
                                @if($menuPastiInfoPendingCount > 0)
                                    <span class="rounded-full bg-amber-500 px-2 py-0.5 text-[10px] font-bold text-white shrink-0">{{ $menuPastiInfoPendingCount > 99 ? '99+' : $menuPastiInfoPendingCount }}</span>
                                @endif
                            </a>
                            
                            <a href="{{ route('programs.index') }}" wire:navigate class="menu-link !py-2 !px-3 {{ request()->routeIs('programs.*') ? 'menu-link-active' : '' }} flex items-center justify-between gap-1">
                                <span>{{ __('messages.programs') }}</span>
                                @if($menuUpcomingProgramCount > 0)
                                    <span class="rounded-full bg-primary px-2 py-0.5 text-[10px] font-bold text-white shrink-0">{{ $menuUpcomingProgramCount > 99 ? '99+' : $menuUpcomingProgramCount }}</span>
                                @endif
                            </a>
                            
                            <a href="{{ route('messages.index') }}" wire:navigate class="menu-link !py-2 !px-3 {{ request()->routeIs('messages.*') ? 'menu-link-active' : '' }} flex items-center justify-between gap-1">
                                <span>{{ __('messages.inbox') }}</span>
                                @if($menuInboxCount > 0)
                                    <span class="rounded-full bg-rose-500 px-2 py-0.5 text-[10px] font-bold text-white shrink-0">{{ $menuInboxCount > 99 ? '99+' : $menuInboxCount }}</span>
                                @endif
                            </a>
                            
                            <a href="{{ route('leave-notices.index') }}" wire:navigate class="menu-link !py-2 !px-3 {{ request()->routeIs('leave-notices.*') ? 'menu-link-active' : '' }} flex items-center justify-between gap-1">
                                <span>{{ __('messages.leave_notice') }}</span>
                                @if($menuOnLeaveGuruCount > 0)
                                    <span class="rounded-full bg-indigo-600 px-2 py-0.5 text-[10px] font-bold text-white shrink-0">{{ $menuOnLeaveGuruCount > 99 ? '99+' : $menuOnLeaveGuruCount }}</span>
                                @endif
                            </a>
                        </div>
                    </div>
                @endrole

                <a href="{{ route('claims.index') }}" wire:navigate class="menu-link {{ request()->routeIs('claims.*') ? 'menu-link-active' : '' }} flex items-center justify-between gap-1">
                    <span>{{ __('messages.claim') }}</span>
                    @if($menuPendingClaimsCount > 0)
                        <span class="rounded-full bg-emerald-600 px-2 py-0.5 text-[10px] font-bold text-white shrink-0" style="background-color: #059669 !important;">{{ $menuPendingClaimsCount > 99 ? '99+' : $menuPendingClaimsCount }}</span>
                    @endif
                </a>


                @role('guru')
                    @if(!auth()->user()->hasAnyRole(['master_admin', 'admin']))
                        <a href="{{ route('pemarkahan.index') }}" wire:navigate class="menu-link {{ request()->routeIs('pemarkahan.*') ? 'menu-link-active' : '' }}">{{ __('messages.pemarkahan') }}</a>
                        <a href="{{ route('pasti-information.index') }}" wire:navigate class="menu-link {{ request()->routeIs('pasti-information.*') ? 'menu-link-active' : '' }} flex items-center justify-between">
                            <span>{{ __('messages.maklumat_pasti') }}</span>
                            @if($menuPastiInfoPendingCount > 0)
                                <span class="rounded-full bg-amber-500 px-2 py-0.5 text-[10px] font-bold text-white">{{ $menuPastiInfoPendingCount > 99 ? '99+' : $menuPastiInfoPendingCount }}</span>
                            @endif
                        </a>
                        <a href="{{ route('programs.index') }}" wire:navigate class="menu-link {{ request()->routeIs('programs.*') ? 'menu-link-active' : '' }} flex items-center justify-between">
                            <span>{{ __('messages.programs') }}</span>
                            @if($menuUpcomingProgramCount > 0)
                                <span class="rounded-full bg-primary px-2 py-0.5 text-[10px] font-bold text-white">{{ $menuUpcomingProgramCount > 99 ? '99+' : $menuUpcomingProgramCount }}</span>
                            @endif
                        </a>
                        <a href="{{ route('messages.index') }}" wire:navigate class="menu-link {{ request()->routeIs('messages.*') ? 'menu-link-active' : '' }} flex items-center justify-between">
                            <span>{{ __('messages.inbox') }}</span>
                            @if($menuInboxCount > 0)
                                <span class="rounded-full bg-rose-500 px-2 py-0.5 text-[10px] font-bold text-white">{{ $menuInboxCount > 99 ? '99+' : $menuInboxCount }}</span>
                            @endif
                        </a>
                        <a href="{{ route('leave-notices.index') }}" wire:navigate class="menu-link {{ request()->routeIs('leave-notices.*') ? 'menu-link-active' : '' }} flex items-center justify-between gap-1">
                            <span>{{ __('messages.leave_notice') }}</span>
                            @if($menuOnLeaveGuruCount > 0)
                                <span class="rounded-full bg-indigo-600 px-2 py-0.5 text-[10px] font-bold text-white shrink-0">{{ $menuOnLeaveGuruCount > 99 ? '99+' : $menuOnLeaveGuruCount }}</span>
                            @endif
                        </a>
                        @if(auth()->user()->guru)
                            <a href="{{ route('kpi.guru.show', auth()->user()->guru) }}" wire:navigate class="menu-link {{ request()->routeIs('kpi.guru.show') ? 'menu-link-active' : '' }}">{{ __('messages.my_kpi') }}</a>
                        @endif
                    @endif
                @endrole

            </nav>
        </aside>

        <main class="min-w-0 space-y-4 {{ $isGuruOnly ? 'guru-main-with-bottom-nav' : '' }}">
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
@livewireScripts
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

