<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-bold">{{ $program->title }}</h2>
                <p class="text-sm text-slate-500">
                    {{ $program->program_date?->format('d/m/Y') }}
                    <span class="mx-2 text-slate-300">|</span>
                    {{ $program->program_time?->format('H:i') ?? '-' }}
                    <span class="mx-2 text-slate-300">|</span>
                    {{ $program->location ?? '-' }}
                </p>
            </div>
            <a href="{{ route('programs.index') }}" class="btn btn-outline">{{ __('messages.cancel') }}</a>
        </div>
    </x-slot>

    <div class="card">
        @if($program->banner_url)
            <img src="{{ $program->banner_url }}" alt="{{ $program->title }}" class="mb-4 h-56 w-full rounded-2xl border border-slate-200 object-cover">
        @endif
        <p>
            <strong>{{ __('messages.teachers') }}:</strong>
            {{ $isAllTeachers ? __('messages.program_all_gurus') : __('messages.program_selected_gurus') }}
        </p>
        <p>
            <strong>{{ __('messages.markah') }}:</strong>
            {{ $program->markah }}
        </p>
        <p>
            <strong>{{ __('messages.require_absence_reason') }}:</strong>
            {{ $program->require_absence_reason ? __('messages.yes') : __('messages.no') }}
        </p>
        <p><strong>{{ __('messages.description') }}:</strong> {{ $program->description ?? '-' }}</p>
    </div>

    <div class="mt-4">
        @php
            $statusCodeById = $statuses->mapWithKeys(
                fn ($status) => [(string) $status->id => $status->code]
            );
        @endphp
        <div class="grid gap-4 lg:grid-cols-2">
            @forelse($program->participations as $participation)
                @php
                    $absenceReviewStatus = $participation->absence_reason_status;
                    $absenceReviewLabel = match ($absenceReviewStatus) {
                        \App\Services\ProgramParticipationService::ABSENCE_REASON_APPROVED => __('messages.absence_reason_approved'),
                        \App\Services\ProgramParticipationService::ABSENCE_REASON_REJECTED => __('messages.absence_reason_rejected'),
                        \App\Services\ProgramParticipationService::ABSENCE_REASON_PENDING => __('messages.absence_reason_pending_review'),
                        default => '-',
                    };
                    $absenceReviewClass = match ($absenceReviewStatus) {
                        \App\Services\ProgramParticipationService::ABSENCE_REASON_APPROVED => 'bg-emerald-100 text-emerald-700',
                        \App\Services\ProgramParticipationService::ABSENCE_REASON_REJECTED => 'bg-rose-100 text-rose-700',
                        \App\Services\ProgramParticipationService::ABSENCE_REASON_PENDING => 'bg-amber-100 text-amber-700',
                        default => 'bg-slate-100 text-slate-500',
                    };
                @endphp
                <article data-testid="program-participation-card" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-black text-slate-900">{{ $participation->guru->display_name }}</h3>
                            <p class="mt-1 text-sm text-slate-500">{{ $participation->guru->phone ?? '-' }}</p>
                        </div>
                        <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700">
                            {{ $participation->status?->name ?? '-' }}
                        </span>
                    </div>

                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <div class="rounded-2xl bg-slate-50 px-4 py-3">
                            <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-slate-500">{{ __('messages.status') }}</p>
                            <p class="mt-1 text-sm font-semibold text-slate-800">{{ $participation->status?->name ?? '-' }}</p>
                        </div>
                        @if($program->require_absence_reason)
                            <div class="rounded-2xl bg-slate-50 px-4 py-3">
                                <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-slate-500">{{ __('messages.absence_reason_review') }}</p>
                                <div class="mt-1">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {{ $absenceReviewClass }}">
                                        {{ $absenceReviewLabel }}
                                    </span>
                                </div>
                            </div>
                            <div class="rounded-2xl bg-slate-50 px-4 py-3 sm:col-span-2">
                                <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-slate-500">{{ __('messages.absence_reason') }}</p>
                                <p class="mt-1 text-sm font-semibold text-slate-800">{{ $participation->absence_reason ?? '-' }}</p>
                            </div>
                        @endif
                    </div>

                    @if($canManage || ($canUpdateOwn && $currentGuruId === $participation->guru_id))
                        <div class="mt-4 border-t border-slate-100 pt-4">
                            <div class="space-y-2">
                                <form
                                    method="POST"
                                    action="{{ route('programs.teachers.status.update', [$program, $participation->guru_id]) }}"
                                    class="grid gap-2 {{ $program->require_absence_reason ? 'md:grid-cols-[220px_1fr_auto]' : 'md:grid-cols-[220px_auto]' }} md:items-center"
                                    x-data="{
                                        selectedStatusId: @js((string) $participation->program_status_id),
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
                                            <option value="{{ $status->id }}" @selected($participation->program_status_id === $status->id)>{{ $status->name }}</option>
                                        @endforeach
                                    </select>
                                    @if($program->require_absence_reason)
                                        <div x-show="requiresAbsenceReason()" x-cloak>
                                            <input
                                                type="text"
                                                name="absence_reason"
                                                class="input-base"
                                                placeholder="{{ __('messages.absence_reason_placeholder') }}"
                                                value="{{ old('absence_reason', $participation->absence_reason) }}"
                                            >
                                        </div>
                                    @endif
                                    <button class="btn btn-outline">{{ __('messages.save') }}</button>
                                </form>

                                @if(
                                    $canManage
                                    && filled($participation->absence_reason)
                                    && ($participation->status?->code === 'TIDAK_HADIR')
                                )
                                    <div class="flex flex-wrap gap-2">
                                        <form method="POST" action="{{ route('programs.teachers.absence-review', [$program, $participation->guru_id]) }}">
                                            @csrf
                                            <input type="hidden" name="decision" value="approved">
                                            <button class="btn btn-success btn-sm">{{ __('messages.approve_reason') }}</button>
                                        </form>
                                        <form method="POST" action="{{ route('programs.teachers.absence-review', [$program, $participation->guru_id]) }}">
                                            @csrf
                                            <input type="hidden" name="decision" value="rejected">
                                            <button class="btn btn-error btn-sm">{{ __('messages.reject_reason') }}</button>
                                        </form>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                </article>
            @empty
                <div class="rounded-3xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                    -
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
