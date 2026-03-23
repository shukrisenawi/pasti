<x-app-layout>
    <x-slot name="header">
    @if(auth()->user()->hasRole('guru') && ! auth()->user()->hasAnyRole(['master_admin', 'admin']))
        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.24em] text-primary">Dashboard Guru</p>
                <h2 class="mt-1 text-2xl font-extrabold tracking-tight text-slate-900">{{ auth()->user()->display_name }}</h2>
            </div>
            <div class="text-right">
                <p class="text-xs font-bold uppercase tracking-[0.24em] text-slate-400">Tahun Semasa</p>
                <p class="text-lg font-extrabold text-slate-900">{{ $latestYear }}</p>
            </div>
        </div>
    @else
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.24em] text-primary">Overview</p>
                <h2 class="mt-1 text-2xl font-extrabold tracking-tight text-slate-900">{{ __('messages.dashboard') }}</h2>
                <p class="mt-2 text-sm text-slate-500">{{ __('messages.dashboard_subtitle') }}</p>
            </div>
        </div>
    @endif
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
        <section class="mb-8 space-y-5">
            <div class="overflow-hidden rounded-3xl bg-gradient-to-br from-primary via-primary-dark to-emerald-700 p-5 text-white shadow-xl sm:p-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-3">
                        <x-avatar :user="$user" size="h-14 w-14" rounded="rounded-2xl" border="border border-white/25" />
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.2em] text-white/70">Guru Dashboard</p>
                            <p class="mt-1 text-xl font-black sm:text-2xl">{{ auth()->user()->display_name }}</p>
                            <p class="text-xs text-white/80">{{ $user->email }}</p>
                        </div>
                    </div>
                    <div class="flex w-full flex-nowrap gap-2 sm:w-auto">
                        <div class="min-w-0 flex-1 rounded-2xl border border-white/20 bg-white/10 px-3 py-2">
                            <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-white/70">KPI</p>
                            <p class="mt-1 truncate text-xl font-extrabold">{{ number_format($user->guru?->kpiSnapshot?->score ?? 0, 1) }}%</p>
                        </div>
                        <div class="min-w-0 flex-1 rounded-2xl border border-white/20 bg-white/10 px-3 py-2">
                            <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-white/70">Cuti</p>
                            <p class="mt-1 truncate text-xl font-extrabold">{{ $guruLeaveDays }} Hari</p>
                        </div>
                        <div class="min-w-0 flex-1 rounded-2xl border border-white/20 bg-white/10 px-3 py-2">
                            <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-white/70">Tempoh Mengajar</p>
                            <p class="mt-1 truncate text-xl font-extrabold">{{ $guruTeachingDuration }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <div class="rounded-2xl border border-slate-100 bg-white p-4 shadow-card">
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">KPI Saya</p>
                    <p class="mt-2 text-3xl font-black text-primary">{{ number_format($user->guru?->kpiSnapshot?->score ?? 0, 1) }}<span class="text-sm font-bold text-slate-400">%</span></p>
                </div>
                <div class="rounded-2xl border border-slate-100 bg-white p-4 shadow-card">
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Jumlah Cuti</p>
                    <p class="mt-2 text-3xl font-black text-orange-600">{{ $guruLeaveDays }}</p>
                </div>
                <div class="rounded-2xl border border-slate-100 bg-white p-4 shadow-card">
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Tugasan</p>
                    <p class="mt-2 text-3xl font-black {{ $pendingPastiInfoCount > 0 ? 'text-rose-600' : 'text-emerald-600' }}">{{ $pendingPastiInfoCount }}</p>
                </div>
                <div class="rounded-2xl border border-slate-100 bg-white p-4 shadow-card">
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Notifikasi</p>
                    <p class="mt-2 text-3xl font-black text-blue-600">{{ $user->unreadNotifications()->count() }}</p>
                </div>
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

    @if($userAjkPositions->isNotEmpty())
        <section class="mb-8">
            <div class="rounded-3xl border border-primary/20 bg-gradient-to-br from-white to-primary/5 p-5 shadow-card sm:p-6">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary text-white shadow-lg shadow-primary/20">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.24em] text-primary">{{ __('messages.ajk_program') }}</p>
                        <h3 class="text-lg font-black text-slate-900">{{ __('messages.my_ajk_positions') }}</h3>
                    </div>
                </div>
                <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($userAjkPositions as $position)
                        <div class="group relative flex flex-col gap-1 rounded-2xl border border-primary/10 bg-white p-4 shadow-sm transition-all hover:border-primary/30 hover:shadow-md">
                            <div class="flex items-center gap-2">
                                <div class="h-2 w-2 rounded-full bg-primary animate-pulse"></div>
                                <span class="text-sm font-black text-slate-900">{{ $position->name }}</span>
                            </div>
                            @if($position->description)
                                <p class="text-xs text-slate-500 leading-relaxed pl-4">{{ $position->description }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if($latestPrograms->isNotEmpty())
        <section class="mb-8">
            <div class="flex items-center justify-between mb-4">
               <h3 class="text-sm font-black uppercase tracking-[0.24em] text-slate-400">{{ __('messages.upcoming_programs') }}</h3>
               <a href="{{ route('programs.index') }}" class="text-xs font-bold text-primary hover:underline">Lihat Semua</a>
            </div>
            
            <div class="grid gap-6 xl:grid-cols-[1fr_auto]">
                <div class="space-y-4">
                    @foreach($latestPrograms as $p)
                        <div class="group relative overflow-hidden rounded-3xl border border-slate-100 bg-white p-4 shadow-card transition-all hover:shadow-lg sm:p-5 {{ $loop->first ? 'ring-2 ring-primary/20' : '' }}">
                            @if($loop->first)
                                <div class="absolute top-0 right-0 rounded-bl-2xl bg-primary px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-white">TERBARU</div>
                            @endif
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-slate-50 text-slate-400 group-hover:bg-primary/10 group-hover:text-primary transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4v-4m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h4 class="text-lg font-black text-slate-900">{{ $p->title }}</h4>
                                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-[10px] font-bold text-slate-500">
                                            {{ $p->program_date?->format('d/m/Y') }}
                                        </span>
                                    </div>
                                    <p class="mt-1 flex items-center gap-3 text-xs font-bold text-slate-400">
                                        <span class="flex items-center gap-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            {{ $p->program_time?->format('H:i') ?? '-' }}
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                            {{ $p->location ?? '-' }}
                                        </span>
                                    </p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('programs.show', $p) }}" class="rounded-xl bg-slate-50 px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 transition-colors">
                                        {{ __('messages.view') }}
                                    </a>
                                </div>
                            </div>
                            
                            @if($loop->first && $canUpdateOwnStatus && $currentParticipation)
                                <div class="mt-4 border-t border-slate-50 pt-4">
                                     <form method="POST" action="{{ route('programs.teachers.status.update', [$p, $currentParticipation->guru_id]) }}" class="flex flex-wrap items-center gap-2">
                                        @csrf
                                        <select name="program_status_id" class="text-xs font-bold rounded-xl border-slate-200 bg-slate-50 px-3 py-2 outline-none focus:ring-2 focus:ring-primary/20">
                                            @foreach($statuses as $status)
                                                <option value="{{ $status->id }}" @selected($currentParticipation->program_status_id == $status->id)>
                                                    {{ $status->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <button class="rounded-xl bg-primary px-4 py-2 text-xs font-bold text-white shadow-lg shadow-primary/20 hover:bg-primary-dark transition-all">
                                            {{ __('messages.save') }}
                                        </button>
                                     </form>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                @if($latestProgram)
                <div class="hidden xl:block w-80">
                    <div class="sticky top-6 rounded-3xl bg-gradient-to-br from-slate-900 to-emerald-950 p-6 text-white shadow-xl">
                        <p class="text-[10px] font-bold uppercase tracking-[0.24em] text-white/50">Featured Event</p>
                        <h3 class="mt-3 text-xl font-black leading-tight">{{ $latestProgram->title }}</h3>
                        <p class="mt-4 text-sm leading-relaxed text-white/70">{{ \Illuminate\Support\Str::limit($latestProgram->description, 100) }}</p>
                        
                        <div class="mt-8 space-y-4">
                            <div class="flex items-center gap-3">
                                <div class="h-8 w-8 rounded-lg bg-white/10 flex items-center justify-center text-primary">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4v-4m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                </div>
                                <span class="text-sm font-bold">{{ $latestProgram->program_date?->format('d M Y') }}</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="h-8 w-8 rounded-lg bg-white/10 flex items-center justify-center text-primary">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                </div>
                                <span class="text-sm font-bold truncate">{{ $latestProgram->location ?? '-' }}</span>
                            </div>
                        </div>
                        
                        <a href="{{ route('programs.show', $latestProgram) }}" class="mt-8 flex w-full items-center justify-center rounded-2xl bg-primary py-3 text-sm font-bold text-white transition-all hover:bg-primary-dark">
                            Detail Program
                        </a>
                    </div>
                </div>
                @endif
            </div>
        </section>
    @else
        <div class="card mb-8 text-sm text-slate-500 bg-white border border-dashed border-slate-200 text-center py-10">
            <div class="flex flex-col items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-slate-200 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4v-4m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                <p>{{ __('messages.program_terbaru') }}: tiada rekod akan datang</p>
            </div>
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

    @role('guru')
        <section class="mt-5">
            <div class="rounded-3xl border border-slate-100 bg-white p-4 shadow-card sm:p-5">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="text-sm font-black uppercase tracking-[0.2em] text-slate-500">Akses Pantas</h3>
                    <span class="text-xs font-semibold text-slate-400">Desktop & Mobile Friendly</span>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <a href="{{ route('leave-notices.create') }}" class="rounded-2xl border border-orange-100 bg-orange-50 px-3 py-4 text-center transition hover:-translate-y-0.5">
                        <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-xl bg-orange-100 text-orange-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        </div>
                        <p class="mt-2 text-xs font-bold text-slate-700">Minta Cuti</p>
                    </a>
                    <a href="{{ route('pasti.self.edit') }}" class="rounded-2xl border border-primary/10 bg-primary/5 px-3 py-4 text-center transition hover:-translate-y-0.5">
                        <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-xl bg-primary/15 text-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                        </div>
                        <p class="mt-2 text-xs font-bold text-slate-700">Pasti Saya</p>
                    </a>
                    <a href="{{ route('pasti-information.index') }}" class="rounded-2xl border border-emerald-100 bg-emerald-50 px-3 py-4 text-center transition hover:-translate-y-0.5">
                        <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        </div>
                        <p class="mt-2 text-xs font-bold text-slate-700">Info Pasti</p>
                    </a>
                    <a href="{{ route('pemarkahan.index') }}" class="rounded-2xl border border-purple-100 bg-purple-50 px-3 py-4 text-center transition hover:-translate-y-0.5">
                        <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-xl bg-purple-100 text-purple-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                        </div>
                        <p class="mt-2 text-xs font-bold text-slate-700">Pemarkahan</p>
                    </a>
                </div>
            </div>
        </section>
    @endrole
</x-app-layout>


