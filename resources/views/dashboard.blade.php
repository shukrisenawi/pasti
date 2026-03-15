<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.24em] text-primary">Overview</p>
                <h2 class="mt-1 text-2xl font-extrabold tracking-tight text-slate-900">{{ __('messages.dashboard') }}</h2>
                <p class="mt-2 text-sm text-slate-500">{{ __('messages.dashboard_subtitle') }}</p>
            </div>
        </div>
    </x-slot>

    @if($latestInboxMessage)
        @php($latestMessageActivity = $latestInboxMessage->replies_max_created_at ?? $latestInboxMessage->created_at)
        <section class="mb-5">
            <article class="card border-primary/20 bg-white/95">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-xs font-bold uppercase tracking-[0.22em] text-primary">{{ __('messages.latest_message') }}</p>
                        <h3 class="mt-2 truncate text-xl font-extrabold tracking-tight text-slate-900">{{ $latestInboxMessage->title }}</h3>
                        <p class="mt-1 text-xs text-slate-500">
                            {{ __('messages.from') }}: {{ $latestInboxMessage->sender?->display_name ?? '-' }}
                            <span class="mx-2 text-slate-300">|</span>
                            {{ $latestMessageActivity?->diffForHumans() }}
                        </p>
                    </div>
                    <a href="{{ route('messages.show', $latestInboxMessage) }}" class="btn btn-outline">{{ __('messages.view') }}</a>
                </div>
                <p class="mt-3 text-sm leading-7 text-slate-600">{{ \Illuminate\Support\Str::limit($latestInboxMessage->body, 190) }}</p>
            </article>
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
                        <h3 class="mt-2 text-2xl font-extrabold tracking-tight text-slate-900">Guru KPI Tertinggi ({{ $currentYear }})</h3>
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
