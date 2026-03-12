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
            <strong>{{ __('messages.require_absence_reason') }}:</strong>
            {{ $program->require_absence_reason ? __('messages.yes') : __('messages.no') }}
        </p>
        <p><strong>{{ __('messages.description') }}:</strong> {{ $program->description ?? '-' }}</p>
    </div>

    <div class="table-wrap mt-4">
        @php($statusCodeById = $statuses->mapWithKeys(fn ($status) => [(string) $status->id => $status->code]))
        <table class="table-base">
            <thead>
            <tr>
                <th>{{ __('messages.name') }}</th>
                <th>{{ __('messages.phone') }}</th>
                <th>{{ __('messages.status') }}</th>
                @if($program->require_absence_reason)
                    <th>{{ __('messages.absence_reason') }}</th>
                @endif
                @if($canManage || $canUpdateOwn)
                    <th>{{ __('messages.actions') }}</th>
                @endif
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($program->participations as $participation)
                <tr>
                    <td>{{ $participation->guru->display_name }}</td>
                    <td>{{ $participation->guru->phone ?? '-' }}</td>
                    <td>{{ $participation->status?->name ?? '-' }}</td>
                    @if($program->require_absence_reason)
                        <td>{{ $participation->absence_reason ?? '-' }}</td>
                    @endif
                    @if($canManage || ($canUpdateOwn && $currentGuruId === $participation->guru_id))
                        <td>
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
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ (($canManage || $canUpdateOwn) ? 4 : 3) + ($program->require_absence_reason ? 1 : 0) }}" class="text-center">-</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</x-app-layout>

