<div class="grid items-start gap-3 md:grid-cols-2">
    @forelse($participations as $participation)
        <article data-testid="program-complete-card" class="self-start rounded-2xl border border-slate-200 bg-white px-3 py-2.5 shadow-sm">
            <div class="flex items-start gap-3">
                <div class="shrink-0">
                    <button
                        type="button"
                        data-testid="program-complete-avatar-button"
                        class="rounded-xl transition hover:scale-[1.02] focus:outline-none focus:ring-2 focus:ring-primary/30"
                        x-on:click="$dispatch('open-modal', 'complete-participation-modal-{{ $participation->guru_id }}')"
                    >
                        <x-avatar :guru="$participation->guru" size="h-10 w-10" rounded="rounded-xl" border="border border-slate-200" />
                    </button>
                </div>

                <div class="min-w-0 flex-1">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="truncate text-sm font-black text-slate-900">{{ $participation->guru->display_name }}</h3>
                        <span class="inline-flex shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-700">
                            {{ $participation->status?->name ?? '-' }}
                        </span>
                    </div>

                    @if($participation->status?->code !== 'HADIR')
                        <p class="mt-1 truncate text-xs text-slate-500">
                            <span class="font-semibold text-slate-600">{{ __('messages.absence_reason') }}:</span>
                            {{ $participation->absence_reason ?? '-' }}
                        </p>
                    @endif
                </div>
            </div>
        </article>

        <x-modal name="complete-participation-modal-{{ $participation->guru_id }}" :show="false" maxWidth="lg" focusable>
            <div class="p-6" data-testid="program-complete-edit-modal">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <x-avatar :guru="$participation->guru" size="h-12 w-12" rounded="rounded-2xl" border="border border-slate-200" />
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.16em] text-primary">Kemaskini Kehadiran</p>
                            <h3 class="text-lg font-black text-slate-900">{{ $participation->guru->display_name }}</h3>
                        </div>
                    </div>
                    <button type="button" class="btn btn-ghost btn-sm h-9 w-9 rounded-full p-0" x-on:click="$dispatch('close-modal', 'complete-participation-modal-{{ $participation->guru_id }}')">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form
                    method="POST"
                    action="{{ route('programs.teachers.status.update', [$program, $participation->guru_id]) }}"
                    class="mt-5 space-y-4"
                    x-data="{
                        selectedStatusId: @js((string) $participation->program_status_id),
                        statusCodeById: @js($statusCodeById),
                        requiresAbsenceReason() {
                            return this.statusCodeById[this.selectedStatusId] === 'TIDAK_HADIR';
                        }
                    }"
                >
                    @csrf
                    <input type="hidden" name="admin_tab" value="complete">

                    <div>
                        <label class="label-base">{{ __('messages.status') }}</label>
                        <select name="program_status_id" class="input-base" x-model="selectedStatusId">
                            <option value="">-</option>
                            @foreach($statuses as $status)
                                <option value="{{ $status->id }}" @selected($participation->program_status_id === $status->id)>{{ $status->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div x-show="requiresAbsenceReason()" x-cloak>
                        <label class="label-base">{{ __('messages.absence_reason') }}</label>
                        <textarea
                            name="absence_reason"
                            rows="3"
                            class="input-base"
                            placeholder="{{ __('messages.absence_reason_placeholder') }}"
                        >{{ old('absence_reason', $participation->absence_reason) }}</textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" class="btn btn-outline" x-on:click="$dispatch('close-modal', 'complete-participation-modal-{{ $participation->guru_id }}')">{{ __('messages.cancel') }}</button>
                        <button class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </x-modal>
    @empty
        <div class="rounded-3xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500 md:col-span-2">
            {{ $emptyMessage ?? '-' }}
        </div>
    @endforelse
</div>
