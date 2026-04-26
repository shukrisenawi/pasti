<x-app-layout>
    <x-slot name="header">
    @if(auth()->user()->isOperatingAsGuru())
        <div class="hidden items-center justify-between gap-3 min-[360px]:flex">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.24em] text-primary">Dashboard Guru</p>
                <h2 class="mt-1 text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">{{ auth()->user()->display_name }}</h2>
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

    @if(($birthdayUsers ?? collect())->isNotEmpty())
        <div 
            x-data 
            x-init="setTimeout(() => { window.balloons(); }, 500)"
            class="mb-6 rounded-2xl border border-emerald-200 bg-gradient-to-r from-emerald-50 via-white to-emerald-50 p-4 shadow-sm"
        >
            <div class="space-y-1">
                @foreach($birthdayUsers as $birthdayUser)
                    <div class="flex items-center gap-3">
                        <x-avatar :user="$birthdayUser" size="h-[50px] w-[50px]" rounded="rounded-xl" border="border border-emerald-200" />
                        <p class="text-sm font-semibold text-emerald-900 sm:text-base">
                            Selamat Hari Jadi {{ $birthdayUser->display_name }}, ikhlas daripada PASTI Kawasan Sik. Semoga dipanjangkan umur dalam keberkatan dan dimurahkan rezeki.
                        </p>
                    </div>
                @endforeach
            </div>
        </div>
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

    @if($user->isOperatingAsGuru())
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
                    <div class="grid w-full grid-cols-2 gap-2 sm:w-auto sm:grid-cols-3">
                        <div class="min-w-0 rounded-2xl border border-white/20 bg-white/10 px-3 py-2">
                            <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-white/70">KPI</p>
                            <p class="mt-1 truncate text-xl font-extrabold">{{ number_format($user->guru?->kpiSnapshot?->score ?? 0, 1) }}%</p>
                        </div>
                        <div class="min-w-0 rounded-2xl border border-white/20 bg-white/10 px-3 py-2">
                            <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-white/70">Cuti</p>
                            <p class="mt-1 truncate text-xl font-extrabold">{{ $guruLeaveDays }} Hari</p>
                        </div>
                        <div class="col-span-2 min-w-0 rounded-2xl border border-white/20 bg-white/10 px-3 py-2 sm:col-span-1">
                            <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-white/70">Tempoh Mengajar</p>
                            <p class="mt-1 truncate text-xl font-extrabold">{{ $guruTeachingDuration }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="mb-8">
            <div class="rounded-3xl border {{ (($pendingPastiInfoRequest ?? null) || ($pendingGuruSalaryRequest ?? null)) ? 'border-amber-200 bg-gradient-to-br from-amber-50 via-white to-orange-50' : 'border-slate-100 bg-white' }} p-5 shadow-card sm:p-6">
                <div class="flex items-start gap-3">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl {{ (($pendingPastiInfoRequest ?? null) || ($pendingGuruSalaryRequest ?? null)) ? 'bg-amber-500 text-white shadow-lg shadow-amber-200/80' : 'bg-slate-100 text-slate-500' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[10px] font-bold uppercase tracking-[0.22em] {{ (($pendingPastiInfoRequest ?? null) || ($pendingGuruSalaryRequest ?? null)) ? 'text-amber-700' : 'text-slate-400' }}">
                            {{ (($pendingPastiInfoRequest ?? null) || ($pendingGuruSalaryRequest ?? null)) ? 'Perlu Tindakan' : 'Kemaskini Maklumat' }}
                        </p>
                        <h3 class="mt-1 text-xl font-black text-slate-900">
                            {{ (($pendingPastiInfoRequest ?? null) || ($pendingGuruSalaryRequest ?? null)) ? 'Tindakan Diperlukan' : 'Maklumat PASTI & Guru' }}
                        </h3>
                        <p class="mt-1 text-sm text-slate-600">
                            {{ (($pendingPastiInfoRequest ?? null) || ($pendingGuruSalaryRequest ?? null)) ? 'Admin telah menghantar permintaan yang masih menunggu maklum balas anda.' : 'Pastikan maklumat guru dan murid terkini telah direkodkan dalam sistem.' }}
                        </p>
                    </div>
                </div>

                <div class="mt-5 grid gap-4 lg:grid-cols-2">
                    <div class="rounded-2xl border {{ $pendingPastiInfoRequest ? 'border-amber-200 bg-white' : 'border-slate-50 bg-slate-50/50' }} p-4 shadow-sm">
                        <p class="text-xs font-bold uppercase tracking-[0.16em] {{ $pendingPastiInfoRequest ? 'text-amber-700' : 'text-slate-400' }}">Maklumat Semasa</p>
                        <h4 class="mt-2 text-lg font-black text-slate-900">Isi maklumat semasa</h4>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Lengkapkan maklumat guru dan murid terkini untuk PASTI anda supaya admin boleh semak data semasa.</p>
                        @if($pendingPastiInfoRequest)
                            <a href="{{ route('pasti-information.edit', $pendingPastiInfoRequest) }}" class="btn mt-4 rounded-2xl border-none bg-amber-500 px-4 text-sm font-bold text-white hover:bg-amber-600 shadow-lg shadow-amber-200/50">
                                Isi sekarang
                            </a>
                        @else
                            <a href="{{ route('pasti-information.index') }}" class="btn mt-4 rounded-2xl border-none bg-slate-200 px-4 text-sm font-bold text-slate-700 hover:bg-slate-300">
                                Lihat Rekod
                            </a>
                        @endif
                    </div>

                    @if($pendingGuruSalaryRequest ?? null)
                        <div class="rounded-2xl border border-sky-200 bg-white p-4 shadow-sm">
                            <p class="text-xs font-bold uppercase tracking-[0.16em] text-sky-700">Maklumat Elaun</p>
                            <h4 class="mt-2 text-lg font-black text-slate-900">Isi maklumat elaun</h4>
                            <p class="mt-2 text-sm leading-6 text-slate-600">Kemaskini maklumat gaji dan elaun semasa anda supaya rekod kewangan guru sentiasa terkini.</p>
                            <a href="{{ route('guru-salary-information.edit', $pendingGuruSalaryRequest) }}" class="btn mt-4 rounded-2xl border-none bg-sky-600 px-4 text-sm font-bold text-white hover:bg-sky-700 shadow-lg shadow-sky-200/50">
                                Isi sekarang
                            </a>
                        </div>
                    @else
                        <div class="rounded-2xl border border-slate-50 bg-slate-50/50 p-4 shadow-sm">
                            <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Maklumat Gaji</p>
                            <h4 class="mt-2 text-lg font-black text-slate-900">Kemaskini Gaji</h4>
                            <p class="mt-2 text-sm leading-6 text-slate-600">Semak dan pastikan maklumat gaji serta elaun anda adalah yang terkini.</p>
                            <a href="{{ route('guru-salary-information.index') }}" class="btn mt-4 rounded-2xl border-none bg-slate-200 px-4 text-sm font-bold text-slate-700 hover:bg-slate-300">
                                Semak Maklumat
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </section>
    @endif

    @if($user->isOperatingAsAdmin())
        <section class="mb-8">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="relative overflow-hidden rounded-2xl border border-emerald-100 bg-white p-5 shadow-card">
                    <div class="relative z-10">
                        <div class="mb-4 inline-flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a5 5 0 00-10 0v2m-2 0h14a1 1 0 011 1v9a1 1 0 01-1 1H5a1 1 0 01-1-1v-9a1 1 0 011-1z" />
                            </svg>
                        </div>
                        <p class="text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-500">{{ __('messages.cash_balance') }}</p>
                        <p class="mt-1 text-4xl font-black tracking-tight {{ $adminCashBalance < 0 ? 'text-rose-600' : 'text-slate-900' }}">
                            RM{{ number_format($adminCashBalance, 2) }}
                        </p>
                        <p class="mt-2 text-sm font-medium text-slate-500">Baki tunai semasa sistem.</p>
                    </div>
                    <span aria-hidden="true" class="pointer-events-none absolute -bottom-10 -right-10 h-28 w-28 rounded-full bg-emerald-100/70"></span>
                </div>

                <div class="relative overflow-hidden rounded-2xl border border-sky-100 bg-white p-5 shadow-card">
                    <div class="relative z-10">
                        <div class="mb-4 inline-flex h-11 w-11 items-center justify-center rounded-xl bg-sky-100 text-sky-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h5M5 5h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z" />
                            </svg>
                        </div>
                        <p class="text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-500">{{ __('messages.bank_balance') }}</p>
                        <p class="mt-1 text-4xl font-black tracking-tight {{ $adminBankBalance < 0 ? 'text-rose-600' : 'text-slate-900' }}">
                            RM{{ number_format($adminBankBalance, 2) }}
                        </p>
                        <p class="mt-2 text-sm font-medium text-slate-500">Baki akaun bank semasa.</p>
                    </div>
                    <span aria-hidden="true" class="pointer-events-none absolute -bottom-10 -right-10 h-28 w-28 rounded-full bg-sky-100/70"></span>
                </div>
            </div>
        </section>
    @endif

    @if($user->isOperatingAsGuru())
        @if(($activeAnnouncements ?? collect())->isNotEmpty())
            <section class="mb-8">
                <div class="rounded-3xl border border-indigo-100 bg-gradient-to-br from-white to-indigo-50 p-5 shadow-card sm:p-6">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-primary">Pengumuman</p>
                        </div>
                    </div>

                    <div class="mt-4 space-y-3">
                        @foreach($activeAnnouncements as $announcement)
                            <div class="shine-border-panel rounded-2xl bg-white p-4 shadow-sm">
                                <p class="mt-2 text-sm leading-6 text-slate-600 whitespace-pre-wrap">{{ $announcement->body }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif
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
               <a href="{{ route('programs.index') }}" wire:navigate class="text-xs font-bold text-primary hover:underline">Lihat Semua</a>
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
                                    <a href="{{ route('programs.show', $p) }}" wire:navigate class="rounded-xl bg-slate-50 px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 transition-colors">
                                        {{ __('messages.view') }}
                                    </a>
                                </div>
                            </div>
                            
                            @if($loop->first && $canUpdateOwnStatus && $currentParticipation)
                                <div class="mt-4 border-t border-slate-50 pt-4">
                                     @php($dashboardStatusCodeById = $statuses->mapWithKeys(fn ($status) => [(string) $status->id => $status->code]))
                                     <form
                                        method="POST"
                                        action="{{ route('programs.teachers.status.update', [$p, $currentParticipation->guru_id]) }}"
                                        class="flex flex-wrap items-center gap-2"
                                        x-data="{
                                            selectedStatusId: @js((string) $currentParticipation->program_status_id),
                                            statusCodeById: @js($dashboardStatusCodeById),
                                            requiresAbsenceReason() {
                                                return this.statusCodeById[this.selectedStatusId] === 'TIDAK_HADIR';
                                            }
                                        }"
                                     >
                                        @csrf
                                        <select name="program_status_id" class="text-xs font-bold rounded-xl border-slate-200 bg-slate-50 px-3 py-2 outline-none focus:ring-2 focus:ring-primary/20" x-model="selectedStatusId">
                                            @foreach($statuses as $status)
                                                <option value="{{ $status->id }}" @selected($currentParticipation->program_status_id == $status->id)>
                                                    {{ $status->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @if($p->require_absence_reason)
                                            <input
                                                x-show="requiresAbsenceReason()"
                                                x-cloak
                                                type="text"
                                                name="absence_reason"
                                                class="min-w-[220px] rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700 outline-none focus:ring-2 focus:ring-primary/20"
                                                placeholder="{{ __('messages.absence_reason_placeholder') }}"
                                                value="{{ old('absence_reason', $currentParticipation->absence_reason) }}"
                                            >
                                        @endif
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
                        
                        <a href="{{ route('programs.show', $latestProgram) }}" wire:navigate class="mt-8 flex w-full items-center justify-center rounded-2xl bg-primary py-3 text-sm font-bold text-white transition-all hover:bg-primary-dark">
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

    @if($user->isOperatingAsGuru())
        <section class="mt-5">
            <div class="rounded-3xl border border-slate-100 bg-white p-4 shadow-card sm:p-5">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="text-sm font-black uppercase tracking-[0.2em] text-slate-500">Akses Pantas</h3>
                    <span class="text-xs font-semibold text-slate-400">Desktop & Mobile Friendly</span>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
                    <a href="{{ route('leave-notices.create') }}" wire:navigate class="rounded-2xl border border-orange-100 bg-orange-50 px-3 py-4 text-center transition hover:-translate-y-0.5">
                        <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-xl bg-orange-100 text-orange-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        </div>
                        <p class="mt-2 text-xs font-bold text-slate-700">Minta Cuti</p>
                    </a>
                    <a href="{{ route('pasti-information.index') }}" wire:navigate class="rounded-2xl border border-amber-100 bg-amber-50 px-3 py-4 text-center transition hover:-translate-y-0.5">
                        <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-xl bg-amber-100 text-amber-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                        </div>
                        <p class="mt-2 text-xs font-bold text-slate-700">Maklumat PASTI</p>
                    </a>
                    <a href="{{ route('pemarkahan.index') }}" wire:navigate class="rounded-2xl border border-purple-100 bg-purple-50 px-3 py-4 text-center transition hover:-translate-y-0.5">
                        <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-xl bg-purple-100 text-purple-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                        </div>
                        <p class="mt-2 text-xs font-bold text-slate-700">Pemarkahan</p>
                    </a>
                    <a
                        href="https://www.facebook.com/pastikawasansik"
                        target="_blank"
                        rel="noopener noreferrer"
                        onclick="return openExternalInChrome(event, 'https://www.facebook.com/pastikawasansik')"
                        class="rounded-2xl border border-primary/10 bg-primary/5 px-3 py-4 text-center transition hover:-translate-y-0.5"
                    >
                        <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-xl bg-primary/15 text-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M22 12.07C22 6.51 17.52 2 12 2S2 6.51 2 12.07c0 5.02 3.66 9.18 8.44 9.93v-7.03H7.9v-2.9h2.54V9.84c0-2.52 1.49-3.91 3.78-3.91 1.09 0 2.24.2 2.24.2v2.48H15.2c-1.24 0-1.63.78-1.63 1.57v1.89h2.77l-.44 2.9h-2.33V22c4.78-.75 8.43-4.91 8.43-9.93Z"/>
                            </svg>
                        </div>
                        <p class="mt-2 text-xs font-bold text-slate-700">Facebook PASTI</p>
                    </a>
                    <a
                        href="https://www.pastimalaysia.com/epasti-online/"
                        target="_blank"
                        rel="noopener noreferrer"
                        onclick="return openExternalInChrome(event, 'https://www.pastimalaysia.com/epasti-online/')"
                        class="rounded-2xl border border-sky-100 bg-sky-50 px-3 py-4 text-center transition hover:-translate-y-0.5"
                    >
                        <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-xl bg-sky-100 text-sky-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 015.657 5.656l-3 3a4 4 0 01-5.657-5.656m-1.414 1.414a4 4 0 01-5.657-5.656l3-3a4 4 0 115.657 5.656" />
                            </svg>
                        </div>
                        <p class="mt-2 text-xs font-bold text-slate-700">ePASTI Online</p>
                    </a>
                    <a href="{{ route('profile.edit') }}" wire:navigate class="rounded-2xl border border-slate-100 bg-slate-50 px-3 py-4 text-center transition hover:-translate-y-0.5">
                        <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                        </div>
                        <p class="mt-2 text-xs font-bold text-slate-700">Profil Saya</p>
                    </a>
                </div>
            </div>
        </section>
    @endif

    @once
        <script>
            function openExternalInChrome(event, targetUrl) {
                const isInApp = Boolean(
                    window.ReactNativeWebView ||
                    window.LRPastiAppBridge ||
                    window.Android ||
                    window.webkit?.messageHandlers?.lrPastiAuth
                );

                if (!isInApp) {
                    return true;
                }

                event.preventDefault();

                const payload = JSON.stringify({
                    type: 'lr-pasti-open-external-url',
                    url: targetUrl,
                    browser: 'chrome',
                });

                if (window.ReactNativeWebView?.postMessage) {
                    window.ReactNativeWebView.postMessage(payload);
                }

                if (window.webkit?.messageHandlers?.lrPastiOpenUrl?.postMessage) {
                    window.webkit.messageHandlers.lrPastiOpenUrl.postMessage({
                        url: targetUrl,
                        browser: 'chrome',
                    });
                    return false;
                }

                if (window.LRPastiAppBridge?.openExternalUrl) {
                    window.LRPastiAppBridge.openExternalUrl(targetUrl, 'chrome');
                    return false;
                }

                if (window.Android?.openExternalUrl) {
                    window.Android.openExternalUrl(targetUrl, 'chrome');
                    return false;
                }

                if (window.Android?.openUrlInChrome) {
                    window.Android.openUrlInChrome(targetUrl);
                    return false;
                }

                const sanitizedUrl = String(targetUrl).replace(/^https?:\/\//, '');
                window.location.href = `intent://${sanitizedUrl}#Intent;scheme=https;package=com.android.chrome;end`;
                return false;
            }
        </script>
    @endonce
</x-app-layout>
