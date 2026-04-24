<x-app-layout>
    <x-slot name="header">
    @if(auth()->user()->hasRole('guru') && ! auth()->user()->hasAnyRole(['master_admin', 'admin']))
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
        $isGuruOnly = $user->hasRole('guru') && ! $user->hasAnyRole(['master_admin', 'admin']);
        $activeAnnouncementCount = ($activeAnnouncements ?? collect())->count();
        $latestProgramCount = ($latestPrograms ?? collect())->count();
        $pastiName = $user->guru?->pasti?->name ?? 'PASTI belum ditetapkan';
        $dashboardDateLabel = now()->translatedFormat('d M Y');
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
        <div class="mb-6 rounded-2xl border border-emerald-200 bg-gradient-to-r from-emerald-50 via-white to-emerald-50 p-4 shadow-sm">
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

    @if($isGuruOnly)
        <section class="mb-10 space-y-6">
            <div class="relative overflow-hidden rounded-[2rem] border border-emerald-200/70 bg-gradient-to-br from-[#0f3f34] via-[#145647] to-[#1b6f5a] p-5 text-white shadow-[0_24px_60px_-24px_rgba(15,63,52,0.55)] sm:p-7">
                <div class="absolute inset-y-0 right-0 w-1/2 bg-[radial-gradient(circle_at_top_right,rgba(255,255,255,0.18),transparent_42%),radial-gradient(circle_at_bottom_right,rgba(255,255,255,0.08),transparent_35%)]"></div>
                <div class="absolute -right-10 top-10 h-32 w-32 rounded-full border border-white/10"></div>
                <div class="absolute bottom-0 right-24 h-24 w-24 translate-y-1/2 rounded-full bg-white/10 blur-2xl"></div>

                <div class="relative z-10 grid gap-6 xl:grid-cols-[minmax(0,1.2fr)_360px]">
                    <div class="space-y-5">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center gap-4">
                                <x-avatar :user="$user" size="h-16 w-16 sm:h-20 sm:w-20" rounded="rounded-[1.5rem]" border="border border-white/20" />
                                <div class="min-w-0">
                                    <p class="text-[11px] font-bold uppercase tracking-[0.28em] text-emerald-100/80">Dashboard Guru</p>
                                    <h2 class="mt-2 truncate text-2xl font-black tracking-tight text-white sm:text-3xl">{{ $user->display_name }}</h2>
                                    <p class="mt-1 text-sm text-emerald-50/80">{{ $pastiName }}</p>
                                </div>
                            </div>
                            <div class="rounded-2xl border border-white/15 bg-white/10 px-4 py-3 text-left backdrop-blur-sm sm:text-right">
                                <p class="text-[10px] font-bold uppercase tracking-[0.24em] text-emerald-50/65">Kemas Kini</p>
                                <p class="mt-1 text-lg font-black">{{ $dashboardDateLabel }}</p>
                            </div>
                        </div>

                        <div class="max-w-2xl">
                            <p class="text-sm leading-7 text-emerald-50/85 sm:text-[15px]">
                                Paparan ringkas untuk semak prestasi semasa, tindakan penting, pengumuman terbaru dan program yang perlu diberi perhatian tanpa perlu buka banyak menu.
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <span class="inline-flex items-center rounded-full border border-white/15 bg-white/10 px-3 py-1.5 text-xs font-semibold text-white/90">KPI {{ number_format($user->guru?->kpiSnapshot?->score ?? 0, 1) }}%</span>
                            <span class="inline-flex items-center rounded-full border border-white/15 bg-white/10 px-3 py-1.5 text-xs font-semibold text-white/90">{{ $guruLeaveDays }} hari cuti</span>
                            <span class="inline-flex items-center rounded-full border border-white/15 bg-white/10 px-3 py-1.5 text-xs font-semibold text-white/90">{{ $activeAnnouncementCount }} pengumuman aktif</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-[1.6rem] border border-white/12 bg-white/10 p-4 backdrop-blur-sm">
                            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-emerald-50/65">Skor KPI</p>
                            <p class="mt-3 text-3xl font-black">{{ number_format($user->guru?->kpiSnapshot?->score ?? 0, 1) }}%</p>
                            <p class="mt-2 text-xs text-emerald-50/75">Prestasi semasa tahun ini</p>
                        </div>
                        <div class="rounded-[1.6rem] border border-white/12 bg-white/10 p-4 backdrop-blur-sm">
                            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-emerald-50/65">Jumlah Cuti</p>
                            <p class="mt-3 text-3xl font-black">{{ $guruLeaveDays }}</p>
                            <p class="mt-2 text-xs text-emerald-50/75">Rekod cuti semasa</p>
                        </div>
                        <div class="rounded-[1.6rem] border border-white/12 bg-white/10 p-4 backdrop-blur-sm">
                            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-emerald-50/65">Tempoh Mengajar</p>
                            <p class="mt-3 text-xl font-black">{{ $guruTeachingDuration }}</p>
                            <p class="mt-2 text-xs text-emerald-50/75">Pengalaman mengajar</p>
                        </div>
                        <div class="rounded-[1.6rem] border border-white/12 bg-white/10 p-4 backdrop-blur-sm">
                            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-emerald-50/65">Program Semasa</p>
                            <p class="mt-3 text-3xl font-black">{{ $latestProgramCount }}</p>
                            <p class="mt-2 text-xs text-emerald-50/75">Program akan datang</p>
                        </div>
                    </div>
                </div>

                <div class="relative z-10 mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <a href="{{ route('leave-notices.create') }}" class="group rounded-[1.6rem] border border-white/12 bg-white/10 p-4 backdrop-blur-sm transition duration-200 hover:-translate-y-0.5 hover:bg-white/14">
                        <div class="flex items-start justify-between gap-3">
                            <div class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-white/14 text-white">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                            </div>
                            <span class="text-[11px] font-bold uppercase tracking-[0.18em] text-emerald-50/60">Tindakan</span>
                        </div>
                        <p class="mt-4 text-base font-black text-white">Minta Cuti</p>
                        <p class="mt-1 text-sm text-emerald-50/75">Hantar notis cuti dengan cepat.</p>
                    </a>

                    <a href="{{ route('pasti.self.edit') }}" class="group rounded-[1.6rem] border border-white/12 bg-white/10 p-4 backdrop-blur-sm transition duration-200 hover:-translate-y-0.5 hover:bg-white/14">
                        <div class="flex items-start justify-between gap-3">
                            <div class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-white/14 text-white">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                            </div>
                            <span class="text-[11px] font-bold uppercase tracking-[0.18em] text-emerald-50/60">Profil</span>
                        </div>
                        <p class="mt-4 text-base font-black text-white">Pasti Saya</p>
                        <p class="mt-1 text-sm text-emerald-50/75">Semak dan kemaskini maklumat.</p>
                    </a>

                    <a href="{{ route('pemarkahan.index') }}" class="group rounded-[1.6rem] border border-white/12 bg-white/10 p-4 backdrop-blur-sm transition duration-200 hover:-translate-y-0.5 hover:bg-white/14">
                        <div class="flex items-start justify-between gap-3">
                            <div class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-white/14 text-white">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                            </div>
                            <span class="text-[11px] font-bold uppercase tracking-[0.18em] text-emerald-50/60">Kelas</span>
                        </div>
                        <p class="mt-4 text-base font-black text-white">Pemarkahan</p>
                        <p class="mt-1 text-sm text-emerald-50/75">Terus ke modul penilaian murid.</p>
                    </a>

                    <a
                        href="https://www.pastimalaysia.com/epasti-online/"
                        target="_blank"
                        rel="noopener noreferrer"
                        onclick="return openPastiMalaysiaExternal(event)"
                        class="group rounded-[1.6rem] border border-white/12 bg-white/10 p-4 backdrop-blur-sm transition duration-200 hover:-translate-y-0.5 hover:bg-white/14"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-white/14 text-white">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 015.657 5.656l-3 3a4 4 0 01-5.657-5.656m-1.414 1.414a4 4 0 01-5.657-5.656l3-3a4 4 0 115.657 5.656" />
                                </svg>
                            </div>
                            <span class="text-[11px] font-bold uppercase tracking-[0.18em] text-emerald-50/60">Luar</span>
                        </div>
                        <p class="mt-4 text-base font-black text-white">ePASTI Online</p>
                        <p class="mt-1 text-sm text-emerald-50/75">Buka laman rasmi dalam tab baharu.</p>
                    </a>
                </div>
            </div>

            <div class="grid gap-6 xl:grid-cols-[minmax(0,1.3fr)_360px]">
                <div class="space-y-6">
                    <section class="rounded-[2rem] border border-slate-200/80 bg-white/95 p-5 shadow-card sm:p-6">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-emerald-700">Program Guru</p>
                                <h3 class="mt-2 text-2xl font-black tracking-tight text-slate-900">{{ __('messages.upcoming_programs') }}</h3>
                                <p class="mt-2 text-sm text-slate-500">Jadual program yang paling hampir dan perlu diberi perhatian.</p>
                            </div>
                            <a href="{{ route('programs.index') }}" class="inline-flex items-center rounded-2xl border border-slate-200 px-4 py-2 text-xs font-bold text-slate-600 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700">
                                Lihat Semua
                            </a>
                        </div>

                        @if($latestPrograms->isNotEmpty())
                            <div class="mt-6 space-y-4">
                                @foreach($latestPrograms as $p)
                                    <div class="rounded-[1.6rem] border {{ $loop->first ? 'border-emerald-200 bg-emerald-50/55' : 'border-slate-200 bg-slate-50/70' }} p-4 sm:p-5">
                                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                            <div class="flex min-w-0 gap-4">
                                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl {{ $loop->first ? 'bg-emerald-100 text-emerald-700' : 'bg-white text-slate-500' }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4v-4m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                                </div>
                                                <div class="min-w-0">
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <h4 class="text-lg font-black text-slate-900">{{ $p->title }}</h4>
                                                        @if($loop->first)
                                                            <span class="inline-flex rounded-full bg-emerald-600 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.16em] text-white">Fokus</span>
                                                        @endif
                                                    </div>
                                                    <div class="mt-2 flex flex-wrap gap-x-4 gap-y-2 text-sm text-slate-500">
                                                        <span class="inline-flex items-center gap-1.5">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4v-4m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                                            {{ $p->program_date?->format('d/m/Y') ?? '-' }}
                                                        </span>
                                                        <span class="inline-flex items-center gap-1.5">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                            {{ $p->program_time?->format('H:i') ?? '-' }}
                                                        </span>
                                                        <span class="inline-flex items-center gap-1.5">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                                            {{ $p->location ?? '-' }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>

                                            <a href="{{ route('programs.show', $p) }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-700 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700">
                                                {{ __('messages.view') }}
                                            </a>
                                        </div>

                                        @if($loop->first && $canUpdateOwnStatus && $currentParticipation)
                                            <div class="mt-4 border-t border-emerald-100 pt-4">
                                                <form method="POST" action="{{ route('programs.teachers.status.update', [$p, $currentParticipation->guru_id]) }}" class="flex flex-wrap items-center gap-2">
                                                    @csrf
                                                    <select name="program_status_id" class="rounded-2xl border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 outline-none focus:ring-2 focus:ring-emerald-100">
                                                        @foreach($statuses as $status)
                                                            <option value="{{ $status->id }}" @selected($currentParticipation->program_status_id == $status->id)>
                                                                {{ $status->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <button class="rounded-2xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white transition hover:bg-emerald-700">
                                                        {{ __('messages.save') }}
                                                    </button>
                                                </form>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="mt-6 rounded-[1.6rem] border border-dashed border-slate-200 bg-slate-50/60 px-5 py-10 text-center">
                                <p class="text-sm font-semibold text-slate-500">{{ __('messages.program_terbaru') }}: tiada rekod akan datang</p>
                            </div>
                        @endif
                    </section>
                </div>

                <div class="space-y-6">
                    @if($latestInboxMessage)
                        @php($latestMessageActivity = $latestInboxMessage->replies_max_created_at ?? $latestInboxMessage->created_at)
                        @php(
                            $latestMessageActivity = is_string($latestMessageActivity) && $latestMessageActivity !== ''
                                ? \Illuminate\Support\Carbon::parse($latestMessageActivity)
                                : $latestMessageActivity
                        )
                        <section class="rounded-[2rem] border border-slate-200 bg-white/95 p-5 shadow-card">
                            <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-emerald-700">{{ __('messages.latest_message') }}</p>
                            <h3 class="mt-3 text-xl font-black tracking-tight text-slate-900">{{ $latestInboxMessage->title }}</h3>
                            <p class="mt-2 text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">
                                {{ $latestInboxMessage->sender?->display_name ?? 'Admin' }} · {{ $latestMessageActivity?->diffForHumans() }}
                            </p>
                            <p class="mt-4 text-sm leading-7 text-slate-600">{{ \Illuminate\Support\Str::limit($latestInboxMessage->body, 140) }}</p>
                            <a href="{{ route('messages.show', $latestInboxMessage) }}" class="mt-5 inline-flex items-center rounded-2xl bg-slate-900 px-4 py-2 text-xs font-bold text-white transition hover:bg-emerald-700">
                                {{ __('messages.view') }}
                            </a>
                        </section>
                    @endif

                    @if(($activeAnnouncements ?? collect())->isNotEmpty())
                        <section class="rounded-[2rem] border border-indigo-100 bg-gradient-to-br from-white to-indigo-50 p-5 shadow-card">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-indigo-600">Pengumuman</p>
                                    <h3 class="mt-2 text-xl font-black tracking-tight text-slate-900">Makluman Terkini</h3>
                                </div>
                                <span class="inline-flex rounded-full bg-indigo-100 px-3 py-1 text-[11px] font-bold text-indigo-700">{{ $activeAnnouncementCount }}</span>
                            </div>

                            <div class="mt-5 space-y-3">
                                @foreach($activeAnnouncements as $announcement)
                                    <div class="rounded-[1.4rem] border border-white bg-white/90 p-4">
                                        <p class="text-sm font-black text-slate-900">{{ $announcement->title }}</p>
                                        <p class="mt-2 text-sm leading-6 text-slate-600 whitespace-pre-wrap">{{ \Illuminate\Support\Str::limit($announcement->body, 140) }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    @if($userAjkPositions->isNotEmpty())
                        <section class="rounded-[2rem] border border-amber-100 bg-white/95 p-5 shadow-card">
                            <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-amber-700">{{ __('messages.ajk_program') }}</p>
                            <h3 class="mt-2 text-xl font-black tracking-tight text-slate-900">{{ __('messages.my_ajk_positions') }}</h3>
                            <div class="mt-5 space-y-3">
                                @foreach($userAjkPositions as $position)
                                    <div class="rounded-[1.4rem] border border-amber-100 bg-amber-50/60 p-4">
                                        <div class="flex items-center gap-2">
                                            <span class="h-2.5 w-2.5 rounded-full bg-amber-500"></span>
                                            <p class="text-sm font-black text-slate-900">{{ $position->name }}</p>
                                        </div>
                                        @if($position->description)
                                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $position->description }}</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endif
                </div>
            </div>
        </section>
    @endif

    @if($user->hasAnyRole(['master_admin', 'admin']))
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

    @if($latestInboxMessage && ! $isGuruOnly)
        @php($latestMessageActivity = $latestInboxMessage->replies_max_created_at ?? $latestInboxMessage->created_at)
        @php(
            $latestMessageActivity = is_string($latestMessageActivity) && $latestMessageActivity !== ''
                ? \Illuminate\Support\Carbon::parse($latestMessageActivity)
                : $latestMessageActivity
        )
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

    @if(! $isGuruOnly)
        @if(($activeAnnouncements ?? collect())->isNotEmpty())
            <section class="mb-8">
                <div class="rounded-3xl border border-indigo-100 bg-gradient-to-br from-white to-indigo-50 p-5 shadow-card sm:p-6">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-indigo-500">Pengumuman</p>
                            <h3 class="mt-1 text-lg font-black text-slate-900">Makluman Untuk Guru</h3>
                        </div>
                    </div>

                    <div class="mt-4 space-y-3">
                        @foreach($activeAnnouncements as $announcement)
                            <div class="rounded-2xl border border-indigo-100 bg-white p-4">
                                <div class="flex flex-wrap items-start justify-between gap-2">
                                    <p class="text-sm font-bold text-slate-900">{{ $announcement->title }}</p>
                                </div>
                                <p class="mt-2 text-sm leading-6 text-slate-600 whitespace-pre-wrap">{{ $announcement->body }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif
    @endif

    @if($userAjkPositions->isNotEmpty() && ! $isGuruOnly)
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

    @if($latestPrograms->isNotEmpty() && ! $isGuruOnly)
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
    @elseif(! $isGuruOnly)
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

    @once
        <script>
            function openPastiMalaysiaExternal(event) {
                const targetUrl = 'https://www.pastimalaysia.com/epasti-online/';
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

                window.location.href = 'intent://www.pastimalaysia.com/epasti-online/#Intent;scheme=https;package=com.android.chrome;end';
                return false;
            }
        </script>
    @endonce
</x-app-layout>
