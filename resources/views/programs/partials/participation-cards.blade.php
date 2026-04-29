<div class="grid gap-3 xl:grid-cols-2">
    @forelse($participations as $participation)
        @php
            $absenceReviewStatus = $participation->absence_reason_status;
            $shouldDisableAdminStatusForm = $canManage
                && session('program_status_success_actor') === 'admin'
                && (int) session('program_status_updated_guru_id') === (int) $participation->guru_id;
            $shouldDisableAdminReviewButtons = $canManage
                && in_array($absenceReviewStatus, [
                    \App\Services\ProgramParticipationService::ABSENCE_REASON_APPROVED,
                    \App\Services\ProgramParticipationService::ABSENCE_REASON_REJECTED,
                ], true);
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
        <article data-testid="program-participation-card" class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div class="flex min-w-0 items-start gap-2.5">
                    <div data-testid="program-participation-avatar" class="shrink-0">
                        <x-avatar :guru="$participation->guru" size="h-9 w-9" rounded="rounded-xl" border="border border-slate-200" />
                    </div>
                    <div class="min-w-0">
                        <h3 class="truncate text-base font-black leading-tight text-slate-900">{{ $participation->guru->display_name }}</h3>
                        <p class="mt-0.5 text-xs text-slate-500">{{ $participation->guru->phone ?? '-' }}</p>
                    </div>
                </div>
                <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-700">
                    {{ $participation->status?->name ?? '-' }}
                </span>
            </div>

            <div class="mt-3 grid gap-2 sm:grid-cols-2">
                <div class="rounded-xl bg-slate-50 px-3 py-2">
                    <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-slate-500">{{ __('messages.status') }}</p>
                    <p class="mt-0.5 text-xs font-semibold text-slate-800">{{ $participation->status?->name ?? '-' }}</p>
                </div>
                @if($program->require_absence_reason)
                    <div class="rounded-xl bg-slate-50 px-3 py-2">
                        <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-slate-500">{{ __('messages.absence_reason_review') }}</p>
                        <div class="mt-0.5">
                            <span class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-bold {{ $absenceReviewClass }}">
                                {{ $absenceReviewLabel }}
                            </span>
                        </div>
                    </div>
                    <div class="rounded-xl bg-slate-50 px-3 py-2 sm:col-span-2">
                        <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-slate-500">{{ __('messages.absence_reason') }}</p>
                        <p class="mt-0.5 text-xs font-semibold leading-5 text-slate-800">{{ $participation->absence_reason ?? '-' }}</p>
                    </div>
                @endif
            </div>

            @if(($canManage || ($canUpdateOwn && $currentGuruId === $participation->guru_id)) && ! ($hideActions ?? false))
                <div class="mt-3 border-t border-slate-100 pt-3">
                    <div class="space-y-2">
                        <form
                            method="POST"
                            action="{{ route('programs.teachers.status.update', [$program, $participation->guru_id]) }}"
                            class="grid gap-2 {{ $program->require_absence_reason ? 'md:grid-cols-[170px_1fr_auto]' : 'md:grid-cols-[170px_auto]' }} md:items-center {{ $shouldDisableAdminStatusForm ? 'opacity-70' : '' }}"
                            @if($shouldDisableAdminStatusForm) data-testid="program-admin-status-form-disabled" @endif
                            x-data="{
                                selectedStatusId: @js((string) $participation->program_status_id),
                                statusCodeById: @js($statusCodeById),
                                requiresAbsenceReason() {
                                    return this.statusCodeById[this.selectedStatusId] === 'TIDAK_HADIR';
                                }
                            }"
                        >
                            @csrf
                            <select name="program_status_id" class="input-base max-w-xs text-xs" x-model="selectedStatusId" @disabled($shouldDisableAdminStatusForm)>
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
                                        class="input-base text-xs"
                                        placeholder="{{ __('messages.absence_reason_placeholder') }}"
                                        value="{{ old('absence_reason', $participation->absence_reason) }}"
                                        @disabled($shouldDisableAdminStatusForm)
                                    >
                                </div>
                            @endif
                            <button class="btn btn-outline btn-sm" @disabled($shouldDisableAdminStatusForm)>{{ __('messages.save') }}</button>
                        </form>

                        @if(
                            $canManage
                            && filled($participation->absence_reason)
                            && ($participation->status?->code === 'TIDAK_HADIR')
                        )
                            <div class="flex flex-wrap gap-2 {{ $shouldDisableAdminReviewButtons ? 'opacity-70' : '' }}" @if($shouldDisableAdminReviewButtons) data-testid="program-admin-review-buttons-disabled" @endif>
                                <form method="POST" action="{{ route('programs.teachers.absence-review', [$program, $participation->guru_id]) }}">
                                    @csrf
                                    <input type="hidden" name="decision" value="approved">
                                    <button class="btn btn-success btn-xs" @disabled($shouldDisableAdminReviewButtons)>{{ __('messages.approve_reason') }}</button>
                                </form>
                                <form method="POST" action="{{ route('programs.teachers.absence-review', [$program, $participation->guru_id]) }}">
                                    @csrf
                                    <input type="hidden" name="decision" value="rejected">
                                    <button class="btn btn-error btn-xs" @disabled($shouldDisableAdminReviewButtons)>{{ __('messages.reject_reason') }}</button>
                                </form>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </article>
    @empty
        <div class="rounded-3xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500 xl:col-span-2">
            {{ $emptyMessage ?? '-' }}
        </div>
    @endforelse
</div>
