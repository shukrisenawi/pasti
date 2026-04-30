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
<div class="panel-shell relative overflow-x-hidden" x-data="{ mobileMenuOpen: false }">
    @php
        $authUser = auth()->user();
        $webViewAuthPayload = \App\Support\WebViewAuthPayload::fromUser($authUser);
        $isGuruOnly = $authUser->isOperatingAsGuru();
        $isAdminCardUser = $authUser->isOperatingAsAdmin();
        $canSwitchAdminGuruMode = $authUser->canSwitchToGuruMode() && session('login_using_admin_role');
        $isSwitchedToGuruMode = $authUser->isInGuruMode();
        $switchModeLabel = $isSwitchedToGuruMode ? 'Tukar ke Admin' : 'Tukar ke Guru';
        $switchModeRoute = $isSwitchedToGuruMode
            ? route('impersonation.switch-to-admin-mode')
            : route('impersonation.switch-to-guru-mode');
        $pastiMenuRoute = $authUser->isOperatingAsGuru() ? route('pasti.self.edit') : null;
        $assistantMenuRoute = $authUser->isOperatingAsGuru() ? route('guru-assistants.index') : null;
        $isImpersonatingGuru = session()->has('impersonator_user_id') || request()->hasCookie('impersonator_user_id');
        $assignedPastiIds = $authUser->assignedPastis()->pluck('pastis.id');
        $sidebarKpi = number_format((float) ($authUser->guru?->kpiSnapshot?->score ?? 0), 1) . '%';
        $sidebarLastLoginDate = $authUser->last_login_at
            ? $authUser->last_login_at->timezone(config('app.timezone'))->format('d/m/Y')
            : '-';
        $sidebarLastLoginTime = $authUser->last_login_at
            ? $authUser->last_login_at->timezone(config('app.timezone'))->format('h:i A')
            : '-';
        $sidebarTeachingDuration = '-';
        if ($authUser->guru?->joined_at) {
            $joinedAt = $authUser->guru->joined_at->startOfDay();
            $today = now()->startOfDay();
            $months = $joinedAt->diffInMonths($today);
            $years = intdiv($months, 12);
            $remainingMonths = $months % 12;

            $durationParts = [];
            if ($years > 0) {
                $durationParts[] = $years.' tahun';
            }

            if ($remainingMonths > 0 || $durationParts === []) {
                $durationParts[] = $remainingMonths.' bulan';
            }

            $sidebarTeachingDuration = implode(' ', $durationParts);
        }
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
                <span class="text-base font-extrabold tracking-tight text-primary">PASTI SIK</span>
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
                    <p class="truncate text-xs font-extrabold text-white">{{ $authUser->display_name }}</p>
                    @if($isAdminCardUser)
                        <p class="truncate text-xs font-semibold text-white/80">Akhir Login:</p>
                        <p class="truncate text-xs font-semibold text-white/80">{{ $sidebarLastLoginDate }}</p>
                        <p class="truncate text-xs font-semibold text-white/80">{{ $sidebarLastLoginTime }}</p>
                    @else
                        <p class="truncate text-xs font-semibold text-white/80">KPI: {{ $sidebarKpi }}</p>
                        <p class="truncate text-xs font-semibold text-white/80">{{ $sidebarTeachingDuration }}</p>
                    @endif
                </div>
            </div>
            @if($canSwitchAdminGuruMode)
                <form method="POST" action="{{ $switchModeRoute }}" class="mt-3">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 rounded-full border border-white/25 bg-white/10 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-white/20" data-testid="role-mode-switch-mobile">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h11m0 0-3-3m3 3-3 3M20 17H9m0 0 3-3m-3 3 3 3" />
                        </svg>
                        {{ $switchModeLabel }}
                    </button>
                </form>
            @endif
        </div>

        {{-- Drawer Navigation --}}
        <nav class="space-y-1 px-3 py-2 text-sm">
            @php
                $isTestReminderUser = function ($query): void {
                    $query->where(function ($nameQuery): void {
                        $nameQuery
                            ->whereRaw('lower(coalesce(name, \'\')) = ?', ['test'])
                            ->orWhereRaw('lower(coalesce(nama_samaran, \'\')) = ?', ['test']);
                    });
                };

                $guruSalaryPendingCountForUser = function ($user) use ($isTestReminderUser): int {
                    $query = \App\Models\Guru::query()
                        ->where('is_assistant', false)
                        ->where('active', true)
                        ->whereNotNull('user_id')
                        ->whereDoesntHave('user', $isTestReminderUser)
                        ->whereHas('salaryRequests', fn ($salaryQuery) => $salaryQuery->whereNull('completed_at'));

                    if ($user->isOperatingAsGuru()) {
                        $query->whereKey($user->guru?->id ?? 0);
                    } elseif ($user->hasRole('admin') && ! $user->hasRole('master_admin')) {
                        $query->whereIn('pasti_id', $user->assignedPastis()->pluck('pastis.id'));
                    }

                    return $query->count();
                };

                $drawerInboxCount = 0;
                $drawerProgramApprovalCount = 0;
                $drawerPastiInfoPendingCount = 0;
                $drawerGuruSalaryPendingCount = 0;
                $drawerOnLeaveGuruCount = 0;
                $drawerPendingClaimsCount = 0;
                $expiredSkimPasCount = 0;

                $drawerInboxCount = $authUser->unreadInboxMessagesCount();
                if ($isGuruOnly) {
                    $drawerGuruIds = $authUser->operatingGuruIds();
                    $drawerProgramApprovalCount = \App\Models\Program::query()
                        ->where(function ($query) use ($drawerGuruIds): void {
                            $query
                                ->whereHas('gurus', fn ($guruQuery) => $guruQuery->whereIn('gurus.id', $drawerGuruIds))
                                ->orWhereNull('pasti_id');
                        })
                        ->whereDoesntHave('participations', function ($query) use ($drawerGuruIds): void {
                            $query
                                ->whereIn('guru_id', $drawerGuruIds)
                                ->whereNotNull('program_status_id');
                        })
                        ->count();
                } else {
                    $drawerProgramApprovalCount = \App\Models\ProgramParticipation::query()
                        ->where('absence_reason_status', \App\Services\ProgramParticipationService::ABSENCE_REASON_PENDING)
                        ->when(
                            $authUser->hasRole('admin') && ! $authUser->hasRole('master_admin'),
                            fn ($q) => $q->whereHas('program', fn ($q2) => $q2->whereIn('pasti_id', $assignedPastiIds))
                        )
                        ->count();
                }
                $drawerPastiInfoPendingCount = \App\Models\PastiInformationRequest::query()
                    ->when($authUser->isOperatingAsGuru(), fn ($q) => $q->where('pasti_id', $authUser->guru?->pasti_id ?? 0))
                    ->when($authUser->hasRole('admin') && !$authUser->hasRole('master_admin'), fn ($q) => $q->whereIn('pasti_id', $authUser->assignedPastis()->pluck('pastis.id')))
                    ->whereDoesntHave('pasti.gurus.user', $isTestReminderUser)
                    ->whereNull('completed_at')
                    ->count();
                $drawerGuruSalaryPendingCount = $guruSalaryPendingCountForUser($authUser);
                $drawerOnLeaveGuruCount = \App\Models\LeaveNotice::query()
                    ->when($authUser->isOperatingAsGuru(), fn ($q) => $q->where('guru_id', $authUser->guru?->id ?? 0))
                    ->when($authUser->hasRole('admin') && ! $authUser->hasRole('master_admin'), fn ($q) => $q->whereHas('guru', fn ($q2) => $q2->whereIn('pasti_id', $authUser->assignedPastis()->pluck('pastis.id'))))
                    ->whereDate('leave_date', '<=', now()->toDateString())
                    ->whereDate('leave_until', '>=', now()->toDateString())
                    ->distinct('guru_id')
                    ->count('guru_id');
                $drawerPendingClaimsCount = $authUser->pending_claims_count;
                $expiredSkimPasCount = \App\Models\User::where('tarikh_exp_skim_pas', '<', now()->startOfDay())->count();
            @endphp

            <a href="{{ route('dashboard') }}" wire:navigate @click="mobileMenuOpen = false" class="menu-link {{ request()->routeIs('dashboard') ? 'menu-link-active' : '' }}">{{ __('messages.dashboard') }}</a>

            @if($isAdminCardUser)
                {{-- Pengurusan Dropdown --}}
                <div x-data="{ open: {{ request()->routeIs(['pasti.*', 'users.gurus.*', 'users.admins.*', 'ajk-program.*', 'n8n-settings.*']) ? 'true' : 'false' }} }" class="space-y-1">
                    <button @click="open = !open" class="menu-link w-full flex items-center justify-between {{ request()->routeIs(['pasti.*', 'users.gurus.*', 'users.admins.*', 'ajk-program.*', 'n8n-settings.*']) ? 'text-primary bg-primary/5' : '' }}">
                        <div class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" /></svg>
                            <span>{{ __('Pengurusan') }}</span>
                        </div>
                        <svg class="h-4 w-4 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                    </button>
                    <div x-show="open" x-cloak x-collapse class="pl-4 space-y-1 border-l-2 border-primary/10 ml-4">
                        <a href="{{ route('pasti.index') }}" wire:navigate @click="mobileMenuOpen = false" class="menu-link !py-2 !px-3 {{ request()->routeIs('pasti.*') ? 'menu-link-active' : '' }}">{{ __('messages.pasti') }}</a>
                        <a href="{{ route('users.gurus.index') }}" wire:navigate @click="mobileMenuOpen = false" class="menu-link !py-2 !px-3 {{ request()->routeIs('users.gurus.*') ? 'menu-link-active' : '' }}">{{ __('messages.guru') }}</a>
                        @role('master_admin')
                            <a href="{{ route('users.admins.index') }}" wire:navigate @click="mobileMenuOpen = false" class="menu-link !py-2 !px-3 {{ request()->routeIs('users.admins.*') ? 'menu-link-active' : '' }}">{{ __('messages.admin_accounts') }}</a>
                            <a href="{{ route('n8n-settings.edit') }}" wire:navigate @click="mobileMenuOpen = false" class="menu-link !py-2 !px-3 {{ request()->routeIs('n8n-settings.*') ? 'menu-link-active' : '' }}">Setting n8n</a>
                        @endrole
                        <a href="{{ route('ajk-program.index') }}" wire:navigate @click="mobileMenuOpen = false" class="menu-link !py-2 !px-3 {{ request()->routeIs('ajk-program.*') ? 'menu-link-active' : '' }}">{{ __('messages.ajk_program') }}</a>
                    </div>
                </div>

                {{-- Laporan/Aktiviti Dropdown --}}
                <div x-data="{ open: {{ request()->routeIs(['financial.*', 'claims.*', 'kpi.gurus.*', 'users.expired-skim-pas', 'pemarkahan.*', 'pasti-information.*', 'guru-salary-information.*', 'pasti-reports.*', 'kursus-guru.*', 'programs.*']) ? 'true' : 'false' }} }" class="space-y-1">
                    <button @click="open = !open" class="menu-link w-full flex items-center justify-between {{ request()->routeIs(['financial.*', 'claims.*', 'kpi.gurus.*', 'users.expired-skim-pas', 'pemarkahan.*', 'pasti-information.*', 'guru-salary-information.*', 'pasti-reports.*', 'kursus-guru.*', 'programs.*']) ? 'text-primary bg-primary/5' : '' }}">
                        <div class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 2v-6m-8-2h12a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h4l2 2z" /></svg>
                            <span>{{ __('Laporan/Aktiviti') }}</span>
                            @if((($drawerPendingClaimsCount ?? 0) > 0 || ($expiredSkimPasCount ?? 0) > 0 || ($drawerPastiInfoPendingCount ?? 0) > 0 || ($drawerGuruSalaryPendingCount ?? 0) > 0 || ($drawerProgramApprovalCount ?? 0) > 0))
                                <div class="dot-pulse-yellow ml-2"></div>
                            @endif
                        </div>
                        <svg class="h-4 w-4 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                    </button>
                    <div x-show="open" x-cloak x-collapse class="pl-4 space-y-1 border-l-2 border-primary/10 ml-4">
                        <a href="{{ route('financial.index') }}" wire:navigate @click="mobileMenuOpen = false" class="menu-link !py-2 !px-3 {{ request()->routeIs('financial.*') ? 'menu-link-active' : '' }}">{{ __('messages.kewangan') }}</a>
                        <a href="{{ route('claims.index') }}" wire:navigate @click="mobileMenuOpen = false" class="menu-link !py-2 !px-3 {{ request()->routeIs('claims.*') ? 'menu-link-active' : '' }} flex items-center justify-between gap-1">
                            <span>{{ __('messages.claim') }}</span>
                            @if(($drawerPendingClaimsCount ?? 0) > 0)<span class="rounded-full bg-emerald-600 px-2 py-0.5 text-[10px] font-bold text-white shrink-0" style="background-color: #059669 !important;">{{ ($drawerPendingClaimsCount ?? 0) > 99 ? '99+' : ($drawerPendingClaimsCount ?? 0) }}</span>@endif
                        </a>
                        <a href="{{ route('kpi.gurus.index') }}" wire:navigate @click="mobileMenuOpen = false" class="menu-link !py-2 !px-3 {{ request()->routeIs('kpi.gurus.*') ? 'menu-link-active' : '' }}">{{ __('messages.kpi_guru') }}</a>
                        <a href="{{ route('users.expired-skim-pas') }}" wire:navigate @click="mobileMenuOpen = false" class="menu-link !py-2 !px-3 {{ request()->routeIs('users.expired-skim-pas') ? 'menu-link-active' : '' }} flex items-center justify-between gap-1">
                            <span class="truncate">{{ __('messages.skim_pas_expired_list') }}</span>
                            @if($expiredSkimPasCount > 0)<span class="rounded-full bg-emerald-600 px-2 py-0.5 text-[10px] font-bold text-white shrink-0">{{ $expiredSkimPasCount }}</span>@endif
                        </a>
                        <a href="{{ route('pemarkahan.index') }}" wire:navigate @click="mobileMenuOpen = false" class="menu-link !py-2 !px-3 {{ request()->routeIs('pemarkahan.*') ? 'menu-link-active' : '' }}">{{ __('messages.pemarkahan') }}</a>
                        <a href="{{ route('pasti-information.index') }}" wire:navigate @click="mobileMenuOpen = false" class="menu-link !py-2 !px-3 {{ request()->routeIs('pasti-information.*') ? 'menu-link-active' : '' }} flex items-center justify-between gap-1">
                            <span>{{ __('messages.maklumat_pasti') }}</span>
                            @if($drawerPastiInfoPendingCount > 0)<span data-testid="menu-pasti-badge" class="rounded-full bg-amber-500 px-2 py-0.5 text-[10px] font-bold text-white shrink-0">{{ $drawerPastiInfoPendingCount > 99 ? '99+' : $drawerPastiInfoPendingCount }}</span>@endif
                        </a>
                        <a href="{{ route('guru-salary-information.index') }}" wire:navigate @click="mobileMenuOpen = false" class="menu-link !py-2 !px-3 {{ request()->routeIs('guru-salary-information.*') ? 'menu-link-active' : '' }} flex items-center justify-between gap-1">
                            <span>{{ __('messages.guru_salary_information') }}</span>
                            @if($drawerGuruSalaryPendingCount > 0)<span class="rounded-full bg-amber-500 px-2 py-0.5 text-[10px] font-bold text-white shrink-0">{{ $drawerGuruSalaryPendingCount > 99 ? '99+' : $drawerGuruSalaryPendingCount }}</span>@endif
                        </a>
                        <a href="{{ route('pasti-reports.index') }}" wire:navigate @click="mobileMenuOpen = false" class="menu-link !py-2 !px-3 {{ request()->routeIs('pasti-reports.*') ? 'menu-link-active' : '' }}">{{ __('messages.laporan_pasti') }}</a>
                        <a href="{{ route('kursus-guru.index') }}" wire:navigate @click="mobileMenuOpen = false" class="menu-link !py-2 !px-3 {{ request()->routeIs('kursus-guru.*') ? 'menu-link-active' : '' }}">{{ __('messages.kursus_guru') }}</a>
                        <a href="{{ route('programs.index') }}" wire:navigate @click="mobileMenuOpen = false" class="menu-link !py-2 !px-3 {{ request()->routeIs('programs.*') ? 'menu-link-active' : '' }} flex items-center justify-between gap-1">
                            <span>{{ __('messages.programs') }}</span>
                            @if($drawerProgramApprovalCount > 0)<span data-testid="menu-program-badge" class="rounded-full bg-primary px-2 py-0.5 text-[10px] font-bold text-white shrink-0">{{ $drawerProgramApprovalCount > 99 ? '99+' : $drawerProgramApprovalCount }}</span>@endif
                        </a>
                    </div>
                </div>

                {{-- Komunikasi & Fail Dropdown --}}
                <div x-data="{ open: {{ request()->routeIs(['messages.*', 'leave-notices.*', 'directory-files.*', 'announcements.*']) ? 'true' : 'false' }} }" class="space-y-1">
                    <button @click="open = !open" class="menu-link w-full flex items-center justify-between {{ request()->routeIs(['messages.*', 'leave-notices.*', 'directory-files.*', 'announcements.*']) ? 'text-primary bg-primary/5' : '' }}">
                        <div class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" /></svg>
                            <span>{{ __('Komunikasi & Fail') }}</span>
                            @if(($drawerInboxCount > 0 || $drawerOnLeaveGuruCount > 0))
                                <div class="dot-pulse-yellow ml-2"></div>
                            @endif
                        </div>
                        <svg class="h-4 w-4 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                    </button>
                    <div x-show="open" x-cloak x-collapse class="pl-4 space-y-1 border-l-2 border-primary/10 ml-4">
                        <a href="{{ route('messages.index') }}" wire:navigate @click="mobileMenuOpen = false" class="menu-link !py-2 !px-3 {{ request()->routeIs('messages.*') ? 'menu-link-active' : '' }} flex items-center justify-between gap-1">
                            <span>{{ __('messages.inbox') }}</span>
                            @if($drawerInboxCount > 0)<span class="rounded-full bg-rose-500 px-2 py-0.5 text-[10px] font-bold text-white shrink-0">{{ $drawerInboxCount > 99 ? '99+' : $drawerInboxCount }}</span>@endif
                        </a>
                        <a href="{{ route('leave-notices.index') }}" wire:navigate @click="mobileMenuOpen = false" class="menu-link !py-2 !px-3 {{ request()->routeIs('leave-notices.*') ? 'menu-link-active' : '' }} flex items-center justify-between gap-1">
                            <span>{{ __('messages.leave_notice') }}</span>
                            @if($drawerOnLeaveGuruCount > 0)<span class="rounded-full bg-orange-500 px-2 py-0.5 text-[10px] font-bold text-white shrink-0">{{ $drawerOnLeaveGuruCount > 99 ? '99+' : $drawerOnLeaveGuruCount }}</span>@endif
                        </a>
                        <a href="{{ route('announcements.index') }}" wire:navigate @click="mobileMenuOpen = false" class="menu-link !py-2 !px-3 {{ request()->routeIs('announcements.*') ? 'menu-link-active' : '' }}">Pengumuman</a>
                        <a href="{{ route('directory-files.index') }}" wire:navigate @click="mobileMenuOpen = false" class="menu-link !py-2 !px-3 {{ request()->routeIs('directory-files.*') ? 'menu-link-active' : '' }}">Fail Rujukan</a>
                    </div>
                </div>
            @endif


            @if($isGuruOnly)
                    <div x-data="{ open: {{ request()->routeIs(['kpi.guru.show', 'claims.*', 'pemarkahan.*', 'pasti-information.*', 'guru-salary-information.*', 'kursus-guru.*', 'programs.*']) ? 'true' : 'false' }} }" class="space-y-1">
                        <button @click="open = !open" class="menu-link w-full flex items-center justify-between {{ request()->routeIs(['kpi.guru.show', 'claims.*', 'pemarkahan.*', 'pasti-information.*', 'guru-salary-information.*', 'kursus-guru.*', 'programs.*']) ? 'text-primary bg-primary/5' : '' }}">
                            <div class="flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 2v-6m-8-2h12a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h4l2 2z" /></svg>
                                <span>Prestasi & Tugasan</span>
                                @if((($drawerPendingClaimsCount ?? 0) > 0 || ($drawerPastiInfoPendingCount ?? 0) > 0 || ($drawerGuruSalaryPendingCount ?? 0) > 0 || ($drawerProgramApprovalCount ?? 0) > 0))
                                    <div class="dot-pulse-yellow ml-2"></div>
                                @endif
                            </div>
                            <svg class="h-4 w-4 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                        </button>
                        <div x-show="open" x-cloak x-collapse class="pl-4 space-y-1 border-l-2 border-primary/10 ml-4">
                            @if(auth()->user()->guru)
                                <a href="{{ route('kpi.guru.show', auth()->user()->guru) }}" wire:navigate @click="mobileMenuOpen = false" class="menu-link !py-2 !px-3 {{ request()->routeIs('kpi.guru.show') ? 'menu-link-active' : '' }}">{{ __('messages.my_kpi') }}</a>
                            @endif
                            <a href="{{ route('claims.index') }}" wire:navigate @click="mobileMenuOpen = false" class="menu-link !py-2 !px-3 {{ request()->routeIs('claims.*') ? 'menu-link-active' : '' }} flex items-center justify-between gap-1">
                                <span>{{ __('messages.claim') }}</span>
                                @if(($drawerPendingClaimsCount ?? 0) > 0)<span class="rounded-full bg-emerald-600 px-2 py-0.5 text-[10px] font-bold text-white shrink-0" style="background-color: #059669 !important;">{{ ($drawerPendingClaimsCount ?? 0) > 99 ? '99+' : ($drawerPendingClaimsCount ?? 0) }}</span>@endif
                            </a>
                            <a href="{{ route('pemarkahan.index') }}" wire:navigate @click="mobileMenuOpen = false" class="menu-link !py-2 !px-3 {{ request()->routeIs('pemarkahan.*') ? 'menu-link-active' : '' }}">{{ __('messages.pemarkahan') }}</a>
                            <a href="{{ route('pasti-information.index') }}" wire:navigate @click="mobileMenuOpen = false" class="menu-link !py-2 !px-3 {{ request()->routeIs('pasti-information.*') ? 'menu-link-active' : '' }} flex items-center justify-between">
                                <span>{{ __('messages.maklumat_pasti') }}</span>
                                @if($drawerPastiInfoPendingCount > 0)<span data-testid="menu-pasti-badge" class="rounded-full bg-amber-500 px-2 py-0.5 text-[10px] font-bold text-white">{{ $drawerPastiInfoPendingCount > 99 ? '99+' : $drawerPastiInfoPendingCount }}</span>@endif
                            </a>
                            <a href="{{ route('guru-salary-information.index') }}" wire:navigate @click="mobileMenuOpen = false" class="menu-link !py-2 !px-3 {{ request()->routeIs('guru-salary-information.*') ? 'menu-link-active' : '' }} flex items-center justify-between">
                                <span>{{ __('messages.guru_salary_information') }}</span>
                                @if($drawerGuruSalaryPendingCount > 0)<span class="rounded-full bg-amber-500 px-2 py-0.5 text-[10px] font-bold text-white">{{ $drawerGuruSalaryPendingCount > 99 ? '99+' : $drawerGuruSalaryPendingCount }}</span>@endif
                            </a>
                            <a href="{{ route('kursus-guru.index') }}" wire:navigate @click="mobileMenuOpen = false" class="menu-link !py-2 !px-3 {{ request()->routeIs('kursus-guru.*') ? 'menu-link-active' : '' }}">{{ __('messages.kursus_guru') }}</a>
                            <a href="{{ route('programs.index') }}" wire:navigate @click="mobileMenuOpen = false" class="menu-link !py-2 !px-3 {{ request()->routeIs('programs.*') ? 'menu-link-active' : '' }} flex items-center justify-between">
                                <span>{{ __('messages.programs') }}</span>
                                @if($drawerProgramApprovalCount > 0)<span data-testid="menu-program-badge" class="rounded-full bg-primary px-2 py-0.5 text-[10px] font-bold text-white">{{ $drawerProgramApprovalCount > 99 ? '99+' : $drawerProgramApprovalCount }}</span>@endif
                            </a>
                        </div>
                    </div>

                    <div x-data="{ open: {{ request()->routeIs(['messages.*', 'leave-notices.*', 'directory-files.*']) ? 'true' : 'false' }} }" class="space-y-1">
                        <button @click="open = !open" class="menu-link w-full flex items-center justify-between {{ request()->routeIs(['messages.*', 'leave-notices.*', 'directory-files.*']) ? 'text-primary bg-primary/5' : '' }}">
                            <div class="flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" /></svg>
                                <span>Komunikasi & Fail</span>
                                @if(($drawerInboxCount > 0 || $drawerOnLeaveGuruCount > 0))
                                    <div class="dot-pulse-yellow ml-2"></div>
                                @endif
                            </div>
                            <svg class="h-4 w-4 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                        </button>
                        <div x-show="open" x-cloak x-collapse class="pl-4 space-y-1 border-l-2 border-primary/10 ml-4">
                            <a href="{{ route('messages.index') }}" wire:navigate @click="mobileMenuOpen = false" class="menu-link !py-2 !px-3 {{ request()->routeIs('messages.*') ? 'menu-link-active' : '' }} flex items-center justify-between">
                                <span>{{ __('messages.inbox') }}</span>
                                @if($drawerInboxCount > 0)<span class="rounded-full bg-red-500 px-2 py-0.5 text-[10px] font-bold text-white">{{ $drawerInboxCount > 99 ? '99+' : $drawerInboxCount }}</span>@endif
                            </a>
                            <a href="{{ route('leave-notices.index') }}" wire:navigate @click="mobileMenuOpen = false" class="menu-link !py-2 !px-3 {{ request()->routeIs('leave-notices.*') ? 'menu-link-active' : '' }} flex items-center justify-between gap-1">
                                <span>{{ __('messages.leave_notice') }}</span>
                                @if($drawerOnLeaveGuruCount > 0)<span class="rounded-full bg-orange-500 px-2 py-0.5 text-[10px] font-bold text-white shrink-0">{{ $drawerOnLeaveGuruCount > 99 ? '99+' : $drawerOnLeaveGuruCount }}</span>@endif
                            </a>
                            <a href="{{ route('directory-files.index') }}" wire:navigate @click="mobileMenuOpen = false" class="menu-link !py-2 !px-3 {{ request()->routeIs('directory-files.*') ? 'menu-link-active' : '' }}">Fail Rujukan</a>
                        </div>
                    </div>

                    <div x-data="{ open: {{ request()->routeIs(['guru-directory.*']) ? 'true' : 'false' }} }" class="space-y-1">
                        <button @click="open = !open" class="menu-link w-full flex items-center justify-between {{ request()->routeIs(['guru-directory.*']) ? 'text-primary bg-primary/5' : '' }}">
                            <div class="flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5V4H2v16h5m10 0v-2a4 4 0 00-4-4H9a4 4 0 00-4 4v2m12 0H7m10-11a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                                <span>Direktori</span>
                            </div>
                            <svg class="h-4 w-4 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                        </button>
                        <div x-show="open" x-cloak x-collapse class="pl-4 space-y-1 border-l-2 border-primary/10 ml-4">
                            <a href="{{ route('guru-directory.index') }}" wire:navigate @click="mobileMenuOpen = false" class="menu-link !py-2 !px-3 {{ request()->routeIs('guru-directory.*') ? 'menu-link-active' : '' }}">Senarai Guru</a>
                        </div>
                    </div>
            @endif
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

    <header class="sticky top-0 z-30 border-b border-white/70 bg-white/80 backdrop-blur-xl guru-mobile-fixed-header">
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

                <div class="min-w-0 flex flex-1 items-center gap-2 sm:gap-3">
                    <a href="{{ route('dashboard') }}" wire:navigate class="hidden shrink-0 min-[360px]:block">
                        <x-application-logo class="h-12 w-12 rounded-full border border-primary/20 bg-white object-contain p-1 shadow-sm" />
                    </a>
                    <div class="min-w-0">
                        <a href="{{ route('dashboard') }}" wire:navigate class="whitespace-nowrap text-lg font-extrabold tracking-tight text-primary sm:text-xl">PASTI SIK</a>
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
                            @if($assistantMenuRoute)
                                <a href="{{ $assistantMenuRoute }}" wire:navigate class="mt-1 block rounded-xl px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                                    Pembantu Guru
                                </a>
                            @endif
                            @if($canSwitchAdminGuruMode)
                                <form method="POST" action="{{ $switchModeRoute }}" class="mt-1">
                                    @csrf
                                    <button type="submit" class="block w-full rounded-xl px-3 py-2 text-left text-sm font-semibold text-sky-700 transition hover:bg-sky-50" data-testid="role-mode-switch-dropdown">
                                        {{ $switchModeLabel }}
                                    </button>
                                </form>
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

    <div class="mx-auto grid max-w-7xl {{ request()->routeIs('messages.show') ? 'gap-0 px-0 py-0 sm:px-6 sm:py-6 lg:gap-6 lg:px-8' : 'gap-6 px-4 py-6 sm:px-6 lg:px-8' }} lg:grid-cols-[280px_minmax(0,1fr)] guru-mobile-fixed-header-layout">
        <aside class="card desktop-sidebar h-fit overflow-hidden border-primary/10 bg-white/90">
            <div class="rounded-[1.6rem] bg-gradient-to-br from-primary via-primary-dark to-emerald-700 p-5 text-primary-content shadow-lg">
                <div class="flex items-center gap-4">
                    <x-avatar :user="$authUser" size="h-14 w-14" rounded="rounded-2xl" border="border border-white/20" />
                    <div class="min-w-0">
                        <p class="truncate text-sm font-extrabold text-white">{{ $authUser->display_name }}</p>
                        @if($isAdminCardUser)
                            <p class="truncate text-sm font-semibold text-white/80">Akhir Login:</p>
                            <p class="truncate text-sm font-semibold text-white/80">{{ $sidebarLastLoginDate }}</p>
                            <p class="truncate text-sm font-semibold text-white/80">{{ $sidebarLastLoginTime }}</p>
                        @else
                            <p class="truncate text-sm font-semibold text-white/80">KPI: {{ $sidebarKpi }}</p>
                            <p class="truncate text-sm font-semibold text-white/80">{{ $sidebarTeachingDuration }}</p>
                        @endif
                    </div>
                </div>
                @if($canSwitchAdminGuruMode)
                    <form method="POST" action="{{ $switchModeRoute }}" class="mt-4">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-2 rounded-full border border-white/25 bg-white/10 px-3 py-2 text-xs font-bold text-white transition hover:bg-white/20" data-testid="role-mode-switch-desktop">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h11m0 0-3-3m3 3-3 3M20 17H9m0 0 3-3m-3 3 3 3" />
                            </svg>
                            {{ $switchModeLabel }}
                        </button>
                    </form>
                @endif
            </div>

            <nav class="mt-5 space-y-1.5 text-sm">
                @php
                    $menuInboxCount = $authUser->unreadInboxMessagesCount();

                    if ($isGuruOnly) {
                        $menuGuruIds = $authUser->operatingGuruIds();
                        $menuProgramApprovalCount = \App\Models\Program::query()
                            ->where(function ($query) use ($menuGuruIds): void {
                                $query
                                    ->whereHas('gurus', fn ($guruQuery) => $guruQuery->whereIn('gurus.id', $menuGuruIds))
                                    ->orWhereNull('pasti_id');
                            })
                            ->whereDoesntHave('participations', function ($query) use ($menuGuruIds): void {
                                $query
                                    ->whereIn('guru_id', $menuGuruIds)
                                    ->whereNotNull('program_status_id');
                            })
                            ->count();
                    } else {
                        $menuProgramApprovalCount = \App\Models\ProgramParticipation::query()
                            ->where('absence_reason_status', \App\Services\ProgramParticipationService::ABSENCE_REASON_PENDING)
                            ->when(
                                $authUser->hasRole('admin') && ! $authUser->hasRole('master_admin'),
                                fn ($query) => $query->whereHas('program', fn ($q) => $q->whereIn('pasti_id', $assignedPastiIds))
                            )
                            ->count();
                    }

                    $menuPastiInfoPendingCount = \App\Models\PastiInformationRequest::query()
                        ->when(
                            $authUser->isOperatingAsGuru(),
                            fn ($query) => $query->where('pasti_id', $authUser->guru?->pasti_id ?? 0)
                        )
                        ->when(
                            $authUser->hasRole('admin') && ! $authUser->hasRole('master_admin'),
                            fn ($query) => $query->whereIn('pasti_id', $authUser->assignedPastis()->pluck('pastis.id'))
                        )
                        ->whereDoesntHave('pasti.gurus.user', $isTestReminderUser)
                        ->whereNull('completed_at')
                        ->count();
                    $menuGuruSalaryPendingCount = $guruSalaryPendingCountForUser($authUser);

                    $menuOnLeaveGuruCount = \App\Models\LeaveNotice::query()
                        ->when(
                            $authUser->isOperatingAsGuru(),
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
                
                @if($isAdminCardUser)
                    <!-- Group: Pengurusan -->
                    <div x-data="{ open: {{ request()->routeIs(['pasti.*', 'users.gurus.*', 'users.admins.*', 'ajk-program.*', 'n8n-settings.*']) ? 'true' : 'false' }} }" class="space-y-1">
                        <button @click="open = !open" class="menu-link w-full flex items-center justify-between {{ request()->routeIs(['pasti.*', 'users.gurus.*', 'users.admins.*', 'ajk-program.*', 'n8n-settings.*']) ? 'text-primary bg-primary/5' : '' }}">
                            <div class="flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" /></svg>
                                <span>{{ __('Pengurusan') }}</span>
                            </div>
                            <svg class="h-4 w-4 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                        </button>
                        <div x-show="open" x-cloak x-collapse class="pl-4 space-y-1 border-l-2 border-primary/10 ml-4">
                            <a href="{{ route('pasti.index') }}" wire:navigate class="menu-link !py-2 !px-3 {{ request()->routeIs('pasti.*') ? 'menu-link-active' : '' }}">{{ __('messages.pasti') }}</a>
                            <a href="{{ route('users.gurus.index') }}" wire:navigate class="menu-link !py-2 !px-3 {{ request()->routeIs('users.gurus.*') ? 'menu-link-active' : '' }}">{{ __('messages.guru') }}</a>
                            @role('master_admin')
                                <a href="{{ route('users.admins.index') }}" wire:navigate class="menu-link !py-2 !px-3 {{ request()->routeIs('users.admins.*') ? 'menu-link-active' : '' }}">{{ __('messages.admin_accounts') }}</a>
                                <a href="{{ route('n8n-settings.edit') }}" wire:navigate class="menu-link !py-2 !px-3 {{ request()->routeIs('n8n-settings.*') ? 'menu-link-active' : '' }}">Setting n8n</a>
                            @endrole
                            <a href="{{ route('ajk-program.index') }}" wire:navigate class="menu-link !py-2 !px-3 {{ request()->routeIs('ajk-program.*') ? 'menu-link-active' : '' }}">{{ __('messages.ajk_program') }}</a>
                        </div>
                    </div>

                    <!-- Group: Laporan/Aktiviti -->
                    <div x-data="{ open: {{ request()->routeIs(['financial.*', 'claims.*', 'kpi.gurus.*', 'users.expired-skim-pas', 'pemarkahan.*', 'pasti-information.*', 'guru-salary-information.*', 'pasti-reports.*', 'kursus-guru.*', 'programs.*']) ? 'true' : 'false' }} }" class="space-y-1">
                        <button @click="open = !open" class="menu-link w-full flex items-center justify-between {{ request()->routeIs(['financial.*', 'claims.*', 'kpi.gurus.*', 'users.expired-skim-pas', 'pemarkahan.*', 'pasti-information.*', 'guru-salary-information.*', 'pasti-reports.*', 'kursus-guru.*', 'programs.*']) ? 'text-primary bg-primary/5' : '' }}">
                            <div class="flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 2v-6m-8-2h12a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h4l2 2z" /></svg>
                                <span>{{ __('Laporan/Aktiviti') }}</span>
                                @if(($menuPendingClaimsCount > 0 || $expiredSkimPasCount > 0 || $menuPastiInfoPendingCount > 0 || $menuGuruSalaryPendingCount > 0 || $menuProgramApprovalCount > 0))
                                    <div class="dot-pulse-yellow ml-2"></div>
                                @endif
                            </div>
                            <svg class="h-4 w-4 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                        </button>
                        <div x-show="open" x-cloak x-collapse class="pl-4 space-y-1 border-l-2 border-primary/10 ml-4">
                            <a href="{{ route('financial.index') }}" wire:navigate class="menu-link !py-2 !px-3 {{ request()->routeIs('financial.*') ? 'menu-link-active' : '' }}">{{ __('messages.kewangan') }}</a>
                            <a href="{{ route('claims.index') }}" wire:navigate class="menu-link !py-2 !px-3 {{ request()->routeIs('claims.*') ? 'menu-link-active' : '' }} flex items-center justify-between gap-1">
                                <span>{{ __('messages.claim') }}</span>
                                @if(($menuPendingClaimsCount ?? ($authUser->pending_claims_count ?? 0)) > 0)
                                    <span class="rounded-full bg-emerald-600 px-2 py-0.5 text-[10px] font-bold text-white shrink-0" style="background-color: #059669 !important;">{{ ($menuPendingClaimsCount ?? ($authUser->pending_claims_count ?? 0)) > 99 ? '99+' : ($menuPendingClaimsCount ?? ($authUser->pending_claims_count ?? 0)) }}</span>
                                @endif
                            </a>
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
                                @if(($menuPastiInfoPendingCount ?? 0) > 0)
                                    <span data-testid="menu-pasti-badge" class="rounded-full bg-amber-500 px-2 py-0.5 text-[10px] font-bold text-white shrink-0">{{ ($menuPastiInfoPendingCount ?? 0) > 99 ? '99+' : ($menuPastiInfoPendingCount ?? 0) }}</span>
                                @endif
                            </a>
                            <a href="{{ route('guru-salary-information.index') }}" wire:navigate class="menu-link !py-2 !px-3 {{ request()->routeIs('guru-salary-information.*') ? 'menu-link-active' : '' }} flex items-center justify-between gap-1">
                                <span>{{ __('messages.guru_salary_information') }}</span>
                                @if(($menuGuruSalaryPendingCount ?? 0) > 0)
                                    <span class="rounded-full bg-amber-500 px-2 py-0.5 text-[10px] font-bold text-white shrink-0">{{ ($menuGuruSalaryPendingCount ?? 0) > 99 ? '99+' : ($menuGuruSalaryPendingCount ?? 0) }}</span>
                                @endif
                            </a>
                            <a href="{{ route('pasti-reports.index') }}" wire:navigate class="menu-link !py-2 !px-3 {{ request()->routeIs('pasti-reports.*') ? 'menu-link-active' : '' }}">{{ __('messages.laporan_pasti') }}</a>
                            <a href="{{ route('kursus-guru.index') }}" wire:navigate class="menu-link !py-2 !px-3 {{ request()->routeIs('kursus-guru.*') ? 'menu-link-active' : '' }}">{{ __('messages.kursus_guru') }}</a>
                            
                            <a href="{{ route('programs.index') }}" wire:navigate class="menu-link !py-2 !px-3 {{ request()->routeIs('programs.*') ? 'menu-link-active' : '' }} flex items-center justify-between gap-1">
                                <span>{{ __('messages.programs') }}</span>
                                @if(($menuProgramApprovalCount ?? 0) > 0)
                                    <span data-testid="menu-program-badge" class="rounded-full bg-primary px-2 py-0.5 text-[10px] font-bold text-white shrink-0">{{ ($menuProgramApprovalCount ?? 0) > 99 ? '99+' : ($menuProgramApprovalCount ?? 0) }}</span>
                                @endif
                            </a>
                            
                        </div>
                    </div>

                    <!-- Group: Komunikasi & Fail -->
                    <div x-data="{ open: {{ request()->routeIs(['messages.*', 'leave-notices.*', 'directory-files.*', 'announcements.*']) ? 'true' : 'false' }} }" class="space-y-1">
                        <button @click="open = !open" class="menu-link w-full flex items-center justify-between {{ request()->routeIs(['messages.*', 'leave-notices.*', 'directory-files.*', 'announcements.*']) ? 'text-primary bg-primary/5' : '' }}">
                            <div class="flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" /></svg>
                                <span>{{ __('Komunikasi & Fail') }}</span>
                                @if(($menuInboxCount > 0 || $menuOnLeaveGuruCount > 0))
                                    <div class="dot-pulse-yellow ml-2"></div>
                                @endif
                            </div>
                            <svg class="h-4 w-4 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                        </button>
                        <div x-show="open" x-cloak x-collapse class="pl-4 space-y-1 border-l-2 border-primary/10 ml-4">
                            <a href="{{ route('messages.index') }}" wire:navigate class="menu-link !py-2 !px-3 {{ request()->routeIs('messages.*') ? 'menu-link-active' : '' }} flex items-center justify-between gap-1">
                                <span>{{ __('messages.inbox') }}</span>
                                @if(($menuInboxCount ?? 0) > 0)
                                    <span class="rounded-full bg-red-500 px-2 py-0.5 text-[10px] font-bold text-white shrink-0">{{ ($menuInboxCount ?? 0) > 99 ? '99+' : ($menuInboxCount ?? 0) }}</span>
                                @endif
                            </a>
                            <a href="{{ route('leave-notices.index') }}" wire:navigate class="menu-link !py-2 !px-3 {{ request()->routeIs('leave-notices.*') ? 'menu-link-active' : '' }} flex items-center justify-between gap-1">
                                <span>{{ __('messages.leave_notice') }}</span>
                                @if(($menuOnLeaveGuruCount ?? 0) > 0)
                                    <span class="rounded-full bg-orange-500 px-2 py-0.5 text-[10px] font-bold text-white shrink-0">{{ ($menuOnLeaveGuruCount ?? 0) > 99 ? '99+' : ($menuOnLeaveGuruCount ?? 0) }}</span>
                                @endif
                            </a>
                            <a href="{{ route('announcements.index') }}" wire:navigate class="menu-link !py-2 !px-3 {{ request()->routeIs('announcements.*') ? 'menu-link-active' : '' }}">Pengumuman</a>
                            <a href="{{ route('directory-files.index') }}" wire:navigate class="menu-link !py-2 !px-3 {{ request()->routeIs('directory-files.*') ? 'menu-link-active' : '' }}">Fail Rujukan</a>
                        </div>
                    </div>
                @endif



                @if($isGuruOnly)
                        <div x-data="{ open: {{ request()->routeIs(['kpi.guru.show', 'claims.*', 'pemarkahan.*', 'pasti-information.*', 'guru-salary-information.*', 'kursus-guru.*', 'programs.*']) ? 'true' : 'false' }} }" class="space-y-1">
                            <button @click="open = !open" class="menu-link w-full flex items-center justify-between {{ request()->routeIs(['kpi.guru.show', 'claims.*', 'pemarkahan.*', 'pasti-information.*', 'guru-salary-information.*', 'kursus-guru.*', 'programs.*']) ? 'text-primary bg-primary/5' : '' }}">
                                <div class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 2v-6m-8-2h12a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h4l2 2z" /></svg>
                                    <span>Prestasi & Tugasan</span>
                                    @if(($menuPendingClaimsCount > 0 || $menuPastiInfoPendingCount > 0 || $menuGuruSalaryPendingCount > 0 || $menuProgramApprovalCount > 0))
                                        <div class="dot-pulse-yellow ml-2"></div>
                                    @endif
                                </div>
                                <svg class="h-4 w-4 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </button>
                            <div x-show="open" x-cloak x-collapse class="pl-4 space-y-1 border-l-2 border-primary/10 ml-4">
                                @if(auth()->user()->guru)
                                    <a href="{{ route('kpi.guru.show', auth()->user()->guru) }}" wire:navigate class="menu-link !py-2 !px-3 {{ request()->routeIs('kpi.guru.show') ? 'menu-link-active' : '' }}">{{ __('messages.my_kpi') }}</a>
                                @endif
                                <a href="{{ route('claims.index') }}" wire:navigate class="menu-link !py-2 !px-3 {{ request()->routeIs('claims.*') ? 'menu-link-active' : '' }} flex items-center justify-between gap-1">
                                    <span>{{ __('messages.claim') }}</span>
                                    @if(($menuPendingClaimsCount ?? ($authUser->pending_claims_count ?? 0)) > 0)
                                        <span class="rounded-full bg-emerald-600 px-2 py-0.5 text-[10px] font-bold text-white shrink-0" style="background-color: #059669 !important;">{{ ($menuPendingClaimsCount ?? ($authUser->pending_claims_count ?? 0)) > 99 ? '99+' : ($menuPendingClaimsCount ?? ($authUser->pending_claims_count ?? 0)) }}</span>
                                    @endif
                                </a>
                                <a href="{{ route('pemarkahan.index') }}" wire:navigate class="menu-link !py-2 !px-3 {{ request()->routeIs('pemarkahan.*') ? 'menu-link-active' : '' }}">{{ __('messages.pemarkahan') }}</a>
                                <a href="{{ route('pasti-information.index') }}" wire:navigate class="menu-link !py-2 !px-3 {{ request()->routeIs('pasti-information.*') ? 'menu-link-active' : '' }} flex items-center justify-between">
                                    <span>{{ __('messages.maklumat_pasti') }}</span>
                                    @if(($menuPastiInfoPendingCount ?? 0) > 0)
                                        <span data-testid="menu-pasti-badge" class="rounded-full bg-amber-500 px-2 py-0.5 text-[10px] font-bold text-white">{{ ($menuPastiInfoPendingCount ?? 0) > 99 ? '99+' : ($menuPastiInfoPendingCount ?? 0) }}</span>
                                    @endif
                                </a>
                                <a href="{{ route('guru-salary-information.index') }}" wire:navigate class="menu-link !py-2 !px-3 {{ request()->routeIs('guru-salary-information.*') ? 'menu-link-active' : '' }} flex items-center justify-between">
                                    <span>{{ __('messages.guru_salary_information') }}</span>
                                    @if(($menuGuruSalaryPendingCount ?? 0) > 0)
                                        <span class="rounded-full bg-amber-500 px-2 py-0.5 text-[10px] font-bold text-white">{{ ($menuGuruSalaryPendingCount ?? 0) > 99 ? '99+' : ($menuGuruSalaryPendingCount ?? 0) }}</span>
                                    @endif
                                </a>
                                <a href="{{ route('kursus-guru.index') }}" wire:navigate class="menu-link !py-2 !px-3 {{ request()->routeIs('kursus-guru.*') ? 'menu-link-active' : '' }}">{{ __('messages.kursus_guru') }}</a>
                                <a href="{{ route('programs.index') }}" wire:navigate class="menu-link !py-2 !px-3 {{ request()->routeIs('programs.*') ? 'menu-link-active' : '' }} flex items-center justify-between">
                                    <span>{{ __('messages.programs') }}</span>
                                    @if(($menuProgramApprovalCount ?? 0) > 0)
                                        <span data-testid="menu-program-badge" class="rounded-full bg-primary px-2 py-0.5 text-[10px] font-bold text-white">{{ ($menuProgramApprovalCount ?? 0) > 99 ? '99+' : ($menuProgramApprovalCount ?? 0) }}</span>
                                    @endif
                                </a>
                            </div>
                        </div>

                        <div x-data="{ open: {{ request()->routeIs(['messages.*', 'leave-notices.*', 'directory-files.*']) ? 'true' : 'false' }} }" class="space-y-1">
                            <button @click="open = !open" class="menu-link w-full flex items-center justify-between {{ request()->routeIs(['messages.*', 'leave-notices.*', 'directory-files.*']) ? 'text-primary bg-primary/5' : '' }}">
                                <div class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" /></svg>
                                    <span>Komunikasi & Fail</span>
                                    @if(($menuInboxCount > 0 || $menuOnLeaveGuruCount > 0))
                                        <div class="dot-pulse-yellow ml-2"></div>
                                    @endif
                                </div>
                                <svg class="h-4 w-4 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </button>
                            <div x-show="open" x-cloak x-collapse class="pl-4 space-y-1 border-l-2 border-primary/10 ml-4">
                                <a href="{{ route('messages.index') }}" wire:navigate class="menu-link !py-2 !px-3 {{ request()->routeIs('messages.*') ? 'menu-link-active' : '' }} flex items-center justify-between">
                                    <span>{{ __('messages.inbox') }}</span>
                                    @if(($menuInboxCount ?? 0) > 0)
                                        <span class="rounded-full bg-red-500 px-2 py-0.5 text-[10px] font-bold text-white">{{ ($menuInboxCount ?? 0) > 99 ? '99+' : ($menuInboxCount ?? 0) }}</span>
                                    @endif
                                </a>
                                <a href="{{ route('leave-notices.index') }}" wire:navigate class="menu-link !py-2 !px-3 {{ request()->routeIs('leave-notices.*') ? 'menu-link-active' : '' }} flex items-center justify-between gap-1">
                                    <span>{{ __('messages.leave_notice') }}</span>
                                    @if(($menuOnLeaveGuruCount ?? 0) > 0)
                                        <span class="rounded-full bg-orange-500 px-2 py-0.5 text-[10px] font-bold text-white shrink-0">{{ ($menuOnLeaveGuruCount ?? 0) > 99 ? '99+' : ($menuOnLeaveGuruCount ?? 0) }}</span>
                                    @endif
                                </a>
                                <a href="{{ route('directory-files.index') }}" wire:navigate class="menu-link !py-2 !px-3 {{ request()->routeIs('directory-files.*') ? 'menu-link-active' : '' }}">Fail Rujukan</a>
                            </div>
                        </div>

                        <div x-data="{ open: {{ request()->routeIs(['guru-directory.*']) ? 'true' : 'false' }} }" class="space-y-1">
                            <button @click="open = !open" class="menu-link w-full flex items-center justify-between {{ request()->routeIs(['guru-directory.*']) ? 'text-primary bg-primary/5' : '' }}">
                                <div class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5V4H2v16h5m10 0v-2a4 4 0 00-4-4H9a4 4 0 00-4 4v2m12 0H7m10-11a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                                    <span>Direktori</span>
                                </div>
                                <svg class="h-4 w-4 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </button>
                            <div x-show="open" x-cloak x-collapse class="pl-4 space-y-1 border-l-2 border-primary/10 ml-4">
                                <a href="{{ route('guru-directory.index') }}" wire:navigate class="menu-link !py-2 !px-3 {{ request()->routeIs('guru-directory.*') ? 'menu-link-active' : '' }}">Senarai Guru</a>
                            </div>
                        </div>
                @endif

            </nav>
        </aside>

        <main class="min-w-0 {{ request()->routeIs('messages.show') ? 'space-y-0 lg:space-y-4' : 'space-y-4' }} {{ $isGuruOnly && ! request()->routeIs('messages.show') ? 'guru-main-with-bottom-nav' : '' }}">
            @isset($header)
                <div class="card border-primary/10 bg-white/95 {{ $isGuruOnly && request()->routeIs('dashboard') ? 'hidden min-[360px]:block' : '' }} {{ request()->routeIs('messages.show') ? 'hidden lg:block' : '' }}">
                    {{ $header }}
                </div>
            @endisset

            @if ($isImpersonatingGuru)
                <div class="alert border-amber-300 bg-amber-50 text-amber-900 flex items-center justify-between gap-3 {{ request()->routeIs('messages.show') ? 'hidden lg:flex' : '' }}">
                    <span class="text-sm font-semibold">Anda sedang masuk sebagai guru: {{ $authUser->display_name }}</span>
                    <a href="{{ route('impersonation.stop') }}" class="btn btn-xs btn-outline border-amber-400 text-amber-900 hover:bg-amber-100">Kembali ke Admin</a>
                </div>
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

            @if (session('status'))
                <div class="alert alert-success">
                    {{ session('status') }}
                </div>
            @endif

            <div x-data x-init="$nextTick(() => { $el.classList.remove('opacity-0', 'translate-y-4'); $el.classList.add('opacity-100', 'translate-y-0') })" class="transition-all duration-500 ease-out opacity-0 translate-y-4 transform">
                {{ $slot }}
            </div>
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
<script>
    (function () {
        const authPayload = @json($webViewAuthPayload);
        let lastSentSignature = null;

        function notifyNativeApp(payload) {
            const message = {
                type: 'lr-pasti-auth-user',
                user: payload,
            };

            const signature = JSON.stringify(message);
            if (signature === lastSentSignature) {
                return;
            }

            lastSentSignature = signature;

            window.dispatchEvent(new CustomEvent('lr-pasti:user-identified', {
                detail: message,
            }));

            if (window.ReactNativeWebView?.postMessage) {
                window.ReactNativeWebView.postMessage(signature);
            }

            if (window.webkit?.messageHandlers?.lrPastiAuth?.postMessage) {
                window.webkit.messageHandlers.lrPastiAuth.postMessage(message);
            }

            if (window.LRPastiAppBridge?.onAuthenticatedUser) {
                window.LRPastiAppBridge.onAuthenticatedUser(signature);
            }

            if (window.Android?.onAuthenticatedUser) {
                window.Android.onAuthenticatedUser(signature);
            }
        }

        function syncAuthenticatedUserToApp() {
            notifyNativeApp(authPayload);
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', syncAuthenticatedUserToApp, { once: true });
        } else {
            syncAuthenticatedUserToApp();
        }

        document.addEventListener('livewire:navigated', syncAuthenticatedUserToApp);
        window.addEventListener('pageshow', syncAuthenticatedUserToApp);
    })();
</script>
</body>
</html>
