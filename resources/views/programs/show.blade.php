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

        @if($canRequestReminder)
            <form method="POST" action="{{ route('programs.request-reminder', $program) }}" class="mt-4">
                @csrf
                <button class="btn btn-outline" @disabled(! ($programPendingReminderCount ?? 0))>
                    Minta respond
                </button>
            </form>
        @endif
    </div>

    <div class="mt-4" x-data="{ showAllTeachers: false }">
        @php
            $statusCodeById = $statuses->mapWithKeys(
                fn ($status) => [(string) $status->id => $status->code]
            );
            $programStatusSuccessMessage = session('program_status_success_actor') === 'admin'
                ? session('program_status_success_message')
                : null;
        @endphp
        @if(filled($programStatusSuccessMessage))
            <div data-testid="program-status-success-alert" hidden>{{ $programStatusSuccessMessage }}</div>
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    if (! window.Swal) {
                        return;
                    }

                    window.Swal.fire({
                        icon: 'success',
                        title: @js($programStatusSuccessMessage),
                        timer: 1700,
                        showConfirmButton: false,
                        allowOutsideClick: true,
                    });
                }, { once: true });
            </script>
        @endif
        <div class="mb-3 flex items-center justify-between gap-3">
            <div>
                <h3 class="ml-[20px] text-sm font-black text-slate-900">Guru Terlibat</h3>
            </div>
            <label class="ml-auto inline-flex items-center gap-2 text-xs font-semibold text-slate-600">
                <input
                    type="checkbox"
                    x-model="showAllTeachers"
                    class="h-4 w-4 rounded-full border-slate-300 text-primary focus:ring-primary/30"
                >
                <span>Semua guru</span>
            </label>
        </div>

        <div x-show="!showAllTeachers">
            @include('programs.partials.participation-cards', [
                'participations' => $submittedParticipations,
                'emptyMessage' => 'Belum ada guru yang hantar status kedatangan.',
            ])
        </div>

        <div x-show="showAllTeachers" x-cloak>
            @include('programs.partials.participation-cards', [
                'participations' => $allParticipations,
                'emptyMessage' => 'Tiada guru terlibat untuk program ini.',
            ])
        </div>
    </div>
</x-app-layout>
