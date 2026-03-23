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
<div class="panel-shell relative overflow-hidden">
    @php
        $authUser = auth()->user();
        $isGuruOnly = $authUser->hasRole('guru') && ! $authUser->hasAnyRole(['master_admin', 'admin']);
        $pastiMenuRoute = $authUser->hasAnyRole(['master_admin', 'admin'])
            ? route('pasti.index')
            : ($authUser->hasRole('guru') ? route('pasti.self.edit') : null);
    @endphp
    <div class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-72 bg-gradient-to-b from-primary/10 via-primary/5 to-transparent"></div>

    <header class="sticky top-0 z-30 border-b border-white/70 bg-white/80 backdrop-blur-xl">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="navbar">
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
                @endphp
                <a href="{{ route('dashboard') }}" wire:navigate class="menu-link {{ request()->routeIs('dashboard') ? 'menu-link-active' : '' }}">{{ __('messages.dashboard') }}</a>
                @role('master_admin|admin')
                    <a href="{{ route('financial.index') }}" wire:navigate class="menu-link {{ request()->routeIs('financial.*') ? 'menu-link-active' : '' }}">{{ __('messages.kewangan') }}</a>
                @endrole

                <a href="{{ route('claims.index') }}" wire:navigate class="menu-link {{ request()->routeIs('claims.*') ? 'menu-link-active' : '' }}">
                    {{ __('messages.claim') }}
                </a>

                @role('master_admin')
                    <a href="{{ route('users.admins.index') }}" wire:navigate class="menu-link {{ request()->routeIs('users.admins.*') ? 'menu-link-active' : '' }}">{{ __('messages.admin_accounts') }}</a>
                @endrole

                @role('master_admin|admin')
                    <a href="{{ route('kawasan.index') }}" wire:navigate class="menu-link {{ request()->routeIs('kawasan.*') ? 'menu-link-active' : '' }}">{{ __('messages.kawasan') }}</a>
                    <a href="{{ route('users.gurus.index') }}" wire:navigate class="menu-link {{ request()->routeIs('users.gurus.*') ? 'menu-link-active' : '' }}">{{ __('messages.guru') }}</a>

                    <a href="{{ route('ajk-program.index') }}" wire:navigate class="menu-link {{ request()->routeIs('ajk-program.*') ? 'menu-link-active' : '' }}">{{ __('messages.ajk_program') }}</a>
                    <a href="{{ route('kpi.gurus.index') }}" wire:navigate class="menu-link {{ request()->routeIs('kpi.gurus.*') ? 'menu-link-active' : '' }}">{{ __('messages.kpi_guru') }}</a>
                    
                    @php
                        $expiredSkimPasCount = \App\Models\User::where('tarikh_exp_skim_pas', '<', now()->startOfDay())->count();
                    @endphp
                    <a href="{{ route('users.expired-skim-pas') }}" wire:navigate class="menu-link {{ request()->routeIs('users.expired-skim-pas') ? 'menu-link-active' : '' }} flex items-center justify-between">
                        <span>{{ __('messages.skim_pas_expired_list') }}</span>
                        @if($expiredSkimPasCount > 0)
                            <span class="rounded-full bg-emerald-600 px-2 py-0.5 text-[10px] font-bold text-white shadow-sm" style="background-color: #059669 !important;">{{ $expiredSkimPasCount }}</span>
                        @endif
                    </a>
                @endrole

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
                <a href="{{ route('leave-notices.index') }}" wire:navigate class="menu-link {{ request()->routeIs('leave-notices.*') ? 'menu-link-active' : '' }}">{{ __('messages.leave_notice') }}</a>

                @role('guru')
                    @if(auth()->user()->guru)
                        <a href="{{ route('kpi.guru.show', auth()->user()->guru) }}" wire:navigate class="menu-link {{ request()->routeIs('kpi.guru.show') ? 'menu-link-active' : '' }}">{{ __('messages.my_kpi') }}</a>
                    @endif
                @endrole
            </nav>
        </aside>

        <main class="space-y-4 {{ $isGuruOnly ? 'guru-main-with-bottom-nav' : '' }}">
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






















