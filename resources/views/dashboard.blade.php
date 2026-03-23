<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                @role('guru')
                    <x-avatar :guru="auth()->user()->guru" size="h-12 w-12" rounded="rounded-2xl" border="border-2 border-primary/20" class="lg:hidden" />
                @endrole
                <div>
                    @role('guru')
                        <p class="text-xs font-bold uppercase tracking-[0.24em] text-primary">Selamat Datang</p>
                        <h2 class="mt-1 text-2xl font-extrabold tracking-tight text-slate-900">{{ auth()->user()->display_name }}</h2>
                    @else
                        <p class="text-xs font-bold uppercase tracking-[0.24em] text-primary">Overview</p>
                        <h2 class="mt-1 text-2xl font-extrabold tracking-tight text-slate-900">{{ __('messages.dashboard') }}</h2>
                        <p class="mt-2 text-sm text-slate-500">{{ __('messages.dashboard_subtitle') }}</p>
                    @endrole
                </div>
            </div>
            @role('guru')
                <div class="hidden lg:block">
                    <p class="text-right text-xs font-bold uppercase tracking-[0.24em] text-slate-400">Tahun Semasa</p>
                    <p class="text-right text-lg font-extrabold text-slate-900">{{ $latestYear }}</p>
                </div>
            @endrole
        </div>
    </x-slot>
    
    @php
        $user = auth()->user();
        $skimPasAlert = null;
        if ($user->tarikh_exp_skim_pas) {
            $today = now()->startOfDay();
            $expiry = $user->tarikh_exp_skim_pas->startOfDay();
            
            if ($expiry->lt($today)) {
                $skimPasAlert = 'expired';
            } elseif ($expiry->diffInDays($today) <= 7) {
                $skimPasAlert = 'expiring_soon';
            }
        }
    @endphp

    @if($userAjkPositions->isNotEmpty())
        <section class="mb-6">
            <div class="card border-primary/10 bg-white/95">
                <p class="text-xs font-bold uppercase tracking-[0.22em] text-primary">{{ __('messages.ajk_program') }}</p>
                <h3 class="mt-2 text-lg font-extrabold text-slate-900">{{ __('messages.my_ajk_positions') }}</h3>
                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach($userAjkPositions as $position)
                        <span class="rounded-full bg-primary/10 px-3 py-1 text-xs font-bold text-primary">{{ $position->name }}</span>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if($skimPasAlert === 'expired')
        <div class="mb-6 flex items-center gap-4 rounded-2xl border-2 border-red-500/20 bg-red-50 p-4 text-red-800 shadow-sm">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-red-500 text-white">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
            </div>
            <div>
                <p class="font-black">{{ __('messages.skim_pas_expired') }}</p>
                <p class="text-sm font-bold opacity-80">Tarikh Tamat: {{ $user->tarikh_exp_skim_pas->format('d/m/Y') }}</p>
            </div>
        </div>
    @elseif($skimPasAlert === 'expiring_soon')
        <div class="mb-6 flex items-center gap-4 rounded-2xl border-2 border-amber-500/20 bg-amber-50 p-4 text-amber-800 shadow-sm">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-amber-500 text-white">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
            <div>
                <p class="font-black">{{ __('messages.skim_pas_expiring_soon') }}</p>
                <p class="text-sm font-bold opacity-80">Tarikh Tamat: {{ $user->tarikh_exp_skim_pas->format('d/m/Y') }}</p>
            </div>
        </div>
    @endif

    @role('guru')
        <!-- Guru Mobile Stats & Actions -->
        <section class="mb-8 space-y-8">
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <div class="bg-white rounded-2xl p-4 shadow-card border border-slate-50 flex flex-col justify-between">
                    <div>
                        <div class="h-8 w-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                        </div>
                        <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">KPI Saya</p>
                    </div>
                    <div class="mt-2 flex items-baseline gap-1">
                        <span class="text-2xl font-black text-primary">{{ number_format(auth()->user()->guru?->kpiSnapshot?->score ?? 0, 1) }}</span>
                        <span class="text-xs font-bold text-slate-400">%</span>
                    </div>
                </div>
                <div class="bg-white rounded-2xl p-4 shadow-card border border-slate-50 flex flex-col justify-between">
                    <div>
                        <div class="h-8 w-8 rounded-lg bg-orange-100 flex items-center justify-center text-orange-600 mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4v-4m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        </div>
                        <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Jumlah Cuti</p>
                    </div>
                    <div class="mt-2 flex items-baseline gap-1">
                        <span class="text-2xl font-black text-orange-600">{{ $guruLeaveDays }}</span>
                        <span class="text-xs font-bold text-slate-400">Hari</span>
                    </div>
                </div>
                <div class="bg-white rounded-2xl p-4 shadow-card border border-slate-50 flex flex-col justify-between">
                    <div>
                        <div class="h-8 w-8 rounded-lg {{ $pendingPastiInfoCount > 0 ? 'bg-rose-100 text-rose-600' : 'bg-emerald-100 text-emerald-600' }} flex items-center justify-center mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                        </div>
                        <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Tugasan</p>
                    </div>
                    <div class="mt-2 flex items-baseline gap-1">
                        <span class="text-2xl font-black {{ $pendingPastiInfoCount > 0 ? 'text-rose-600' : 'text-emerald-600' }}">{{ $pendingPastiInfoCount }}</span>
                    </div>
                </div>
                <div class="bg-white rounded-2xl p-4 shadow-card border border-slate-50 flex flex-col justify-between">
                    <div>
                        <div class="h-8 w-8 rounded-lg bg-blue-100 flex items-center justify-center text-blue-600 mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                        </div>
                        <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Notifikasi</p>
                    </div>
                    <div class="mt-2 flex items-baseline gap-1">
                        <span class="text-2xl font-black text-blue-600">{{ auth()->user()->unreadNotifications()->count() }}</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-4 gap-4">
                <a href="{{ route('leave-notices.create') }}" class="group">
                    <div class="flex flex-col items-center">
                        <div class="h-14 w-14 rounded-2xl bg-orange-100 text-orange-600 flex items-center justify-center shadow-sm group-active:scale-95 transition-all mb-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        </div>
                        <span class="text-[10px] font-bold text-slate-600 text-center leading-tight">Minta Cuti</span>
                    </div>
                </a>
                <a href="{{ route('pasti.self.edit') }}" class="group">
                    <div class="flex flex-col items-center">
                        <div class="h-14 w-14 rounded-2xl bg-primary/10 text-primary flex items-center justify-center shadow-sm group-active:scale-95 transition-all mb-2">
                             <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                        </div>
                        <span class="text-[10px] font-bold text-slate-600 text-center leading-tight">Pasti Saya</span>
                    </div>
                </a>
                <a href="{{ route('pasti-information.index') }}" class="group">
                    <div class="flex flex-col items-center">
                        <div class="h-14 w-14 rounded-2xl bg-emerald-100 text-emerald-600 flex items-center justify-center shadow-sm group-active:scale-95 transition-all mb-2">
                             <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        </div>
                        <span class="text-[10px] font-bold text-slate-600 text-center leading-tight">Info Pasti</span>
                    </div>
                </a>
                <a href="{{ route('pemarkahan.index') }}" class="group">
                    <div class="flex flex-col items-center">
                        <div class="h-14 w-14 rounded-2xl bg-purple-100 text-purple-600 flex items-center justify-center shadow-sm group-active:scale-95 transition-all mb-2">
                             <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                        </div>
                        <span class="text-[10px] font-bold text-slate-600 text-center leading-tight">Pemarkahan</span>
                    </div>
                </a>
            </div>
        </section>
    @endrole

    @if($latestInboxMessage)
        @php($latestMessageActivity = $latestInboxMessage->replies_max_created_at ?? $latestInboxMessage->created_at)
        <section class="mb-8">
            <div class="bg-white rounded-2xl p-5 shadow-card border border-slate-50 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 bg-primary/5 rounded-full -mr-16 -mt-16"></div>
                <div class="flex flex-wrap items-start justify-between gap-3 relative z-10">
                    <div class="min-w-0">
                        <p class="text-[10px] font-bold uppercase tracking-[0.22em] text-primary leading-none mb-3">{{ __('messages.latest_message') }}</p>
                        <h3 class="truncate text-lg font-extrabold tracking-tight text-slate-900">{{ $latestInboxMessage->title }}</h3>
                        <p class="mt-1 text-xs text-slate-400 font-medium">
                            {{ $latestInboxMessage->sender?->display_name ?? 'Admin' }} · {{ $latestMessageActivity?->diffForHumans() }}
                        </p>
                    </div>
                    <a href="{{ route('messages.show', $latestInboxMessage) }}" class="inline-flex items-center justify-center px-4 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 transition-colors">
                        {{ __('messages.view') }}
                    </a>
                </div>
                <p class="mt-4 text-sm leading-6 text-slate-600 relative z-10">{{ \Illuminate\Support\Str::limit($latestInboxMessage->body, 120) }}</p>
            </div>
        </section>
    @endif

    @if($latestProgram)
        @php
            $hadirCount = $latestProgram->participations->filter(fn ($p) => $p->status?->code === 'HADIR')->count();
            $tidakHadirCount = $latestProgram->participations->filter(fn ($p) => $p->status?->code === 'TIDAK_HADIR')->count();
        @endphp

        <section class="grid gap-5 xl:grid-cols-[1.3fr_0.7fr]">
            <div class="card border-primary/10 bg-white/95">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.22em] text-primary">{{ __('messages.program_terbaru') }}</p>
                        <h3 class="mt-2 text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">{{ $latestProgram->title }}</h3>
                        <p class="mt-2 text-sm text-slate-500">
                            {{ $latestProgram->program_date?->format('d/m/Y') }}
                            <span class="mx-2 text-slate-300">|</span>
                            {{ $latestProgram->program_time?->format('H:i') ?? '-' }}
                            <span class="mx-2 text-slate-300">|</span>
                            {{ $latestProgram->location ?? '-' }}
                        </p>
                    </div>
                    <a href="{{ route('programs.show', $latestProgram) }}" class="btn btn-outline">{{ __('messages.view') }}</a>
                </div>

                <p class="mt-6 max-w-3xl text-sm leading-7 text-slate-600">{{ $latestProgram->description ?: '-' }}</p>

                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                    <div class="stat-card">
                        <p class="stat-title">{{ __('messages.hadir') }}</p>
                        <p class="stat-value">{{ $hadirCount }}</p>
                    </div>
                    <div class="stat-card">
                        <p class="stat-title">{{ __('messages.tidak_hadir') }}</p>
                        <p class="stat-value">{{ $tidakHadirCount }}</p>
                    </div>
                </div>

                @if($canUpdateOwnStatus)
                    @php
                        $statusCodeById = $statuses->mapWithKeys(fn ($status) => [(string) $status->id => $status->code]);
                        $selectedStatusId = (string) old('program_status_id', $currentParticipation->program_status_id);
                    @endphp
                    <div class="mt-8 rounded-3xl border border-slate-200 bg-slate-50/80 p-4 sm:p-5">
                        <p class="text-sm font-semibold text-slate-700">{{ __('messages.status') }}</p>
                        <form
                            method="POST"
                            action="{{ route('programs.teachers.status.update', [$latestProgram, $currentParticipation->guru_id]) }}"
                            class="mt-4 grid gap-3 {{ $latestProgram->require_absence_reason ? 'md:grid-cols-[220px_1fr_auto]' : 'md:grid-cols-[220px_auto]' }} md:items-center"
                            x-data="{
                                selectedStatusId: @js($selectedStatusId),
                                statusCodeById: @js($statusCodeById),
                                requiresAbsenceReason() {
                                    return this.statusCodeById[this.selectedStatusId] === 'TIDAK_HADIR';
                                }
                            }"
                        >
                            @csrf
                            <select name="program_status_id" class="input-base max-w-xs" x-model="selectedStatusId">
                                <option value="">-</option>
                                @foreach($statuses as $status)
                                    <option value="{{ $status->id }}" @selected(old('program_status_id', $currentParticipation->program_status_id) == $status->id)>
                                        {{ $status->name }}
                                    </option>
                                @endforeach
                            </select>
                            @if($latestProgram->require_absence_reason)
                                <div x-show="requiresAbsenceReason()" x-cloak>
                                    <input
                                        type="text"
                                        name="absence_reason"
                                        class="input-base"
                                        placeholder="{{ __('messages.absence_reason_placeholder') }}"
                                        value="{{ old('absence_reason', $currentParticipation->absence_reason) }}"
                                    >
                                </div>
                            @endif
                            <button class="btn btn-primary">{{ __('messages.save') }}</button>
                        </form>
                    </div>
                @endif
            </div>

            <div class="card bg-gradient-to-br from-slate-900 via-emerald-950 to-primary-dark text-white">
                <p class="text-xs font-bold uppercase tracking-[0.24em] text-white/60">Ringkasan</p>
                <h3 class="mt-3 text-2xl font-extrabold">Status Program Semasa</h3>
                <p class="mt-3 text-sm leading-7 text-white/75">Gunakan panel ini untuk semak program paling baru dan kemas kini kehadiran guru dengan cepat.</p>

                <div class="mt-8 space-y-4">
                    <div class="rounded-2xl border border-white/10 bg-white/10 p-4">
                        <p class="text-xs uppercase tracking-[0.2em] text-white/60">Tarikh</p>
                        <p class="mt-2 text-lg font-bold">{{ $latestProgram->program_date?->format('d/m/Y') ?? '-' }}</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/10 p-4">
                        <p class="text-xs uppercase tracking-[0.2em] text-white/60">{{ __('messages.time') }}</p>
                        <p class="mt-2 text-lg font-bold">{{ $latestProgram->program_time?->format('H:i') ?? '-' }}</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/10 p-4">
                        <p class="text-xs uppercase tracking-[0.2em] text-white/60">Lokasi</p>
                        <p class="mt-2 text-lg font-bold">{{ $latestProgram->location ?? '-' }}</p>
                    </div>
                </div>
            </div>
        </section>
    @else
        <div class="card text-sm text-slate-500">
            {{ __('messages.program_terbaru') }}: -
        </div>
    @endif

    @if($topKpiGurus->isNotEmpty())
        <section class="mt-5">
            <div class="card border-primary/10 bg-white/95">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.22em] text-primary">KPI Guru</p>
                        <h3 class="mt-2 text-2xl font-extrabold tracking-tight text-slate-900">Guru KPI Tertinggi ({{ $latestYear ?? $currentYear }})</h3>
                        <p class="mt-2 text-sm text-slate-500">
                            Turutan: skor KPI paling tinggi, kemudian jumlah cuti paling sedikit.
                        </p>
                    </div>
                    <div class="rounded-2xl border border-primary/20 bg-primary/5 px-4 py-3 text-right">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Skor KPI</p>
                        <p class="mt-1 text-2xl font-extrabold text-primary">{{ number_format((float) ($topKpiGurus->first()?->kpiSnapshot?->score ?? 0), 2) }}%</p>
                    </div>
                </div>

                <div class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach($topKpiGurus as $guru)
                        <div class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4">
                            <div class="flex items-center gap-3">
                                <x-avatar :guru="$guru" size="h-12 w-12" rounded="rounded-2xl" />
                                <div class="min-w-0">
                                    <p class="truncate text-base font-bold text-slate-900">{{ $guru->display_name }}</p>
                                    <p class="truncate text-sm text-slate-500">{{ $guru->pasti?->name ?? '-' }}</p>
                                </div>
                            </div>
                            <div class="mt-3 flex items-center justify-between rounded-xl bg-white px-3 py-2 text-sm">
                                <span class="font-semibold text-slate-600">Jumlah Cuti</span>
                                <span class="font-bold text-slate-900">{{ $guru->leave_notices_current_year_count ?? 0 }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</x-app-layout>
