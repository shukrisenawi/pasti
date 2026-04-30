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
        @php
            $statusCodeById = $statuses->mapWithKeys(
                fn ($status) => [(string) $status->id => $status->code]
            );
        @endphp
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

        @if($canUpdateOwn && blank($currentParticipation?->program_status_id))
            <div class="mt-5 rounded-3xl border border-emerald-200 bg-gradient-to-br from-emerald-50 via-white to-white p-4 shadow-sm">
                <div class="flex items-start gap-3">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-emerald-500 text-white shadow-lg shadow-emerald-200/80">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[10px] font-bold uppercase tracking-[0.22em] text-emerald-700">Perlu Respon</p>
                        <h3 class="mt-1 text-xl font-black text-slate-900">Respon Program</h3>
                        <p class="mt-1 text-sm text-slate-600">Sila pilih status kehadiran anda untuk program ini sebelum admin buat semakan.</p>
                    </div>
                </div>

                <form
                    method="POST"
                    action="{{ route('programs.teachers.status.update', [$program, $currentGuruId]) }}"
                    class="mt-4 grid gap-2 md:grid-cols-[170px_1fr_auto] md:items-center"
                    x-data="{
                        selectedStatusId: @js((string) ($currentParticipation?->program_status_id ?? '')),
                        statusCodeById: @js($statusCodeById),
                        requiresAbsenceReason() {
                            return this.statusCodeById[this.selectedStatusId] === 'TIDAK_HADIR';
                        }
                    }"
                >
                    @csrf
                    <select name="program_status_id" class="input-base max-w-xs text-xs" x-model="selectedStatusId">
                        <option value="">-</option>
                        @foreach($statuses as $status)
                            <option value="{{ $status->id }}" @selected(($currentParticipation?->program_status_id) === $status->id)>{{ $status->name }}</option>
                        @endforeach
                    </select>
                    @if($program->require_absence_reason)
                        <div x-show="requiresAbsenceReason()" x-cloak>
                            <input
                                type="text"
                                name="absence_reason"
                                class="input-base text-xs"
                                placeholder="{{ __('messages.absence_reason_placeholder') }}"
                                value="{{ old('absence_reason', $currentParticipation?->absence_reason) }}"
                            >
                        </div>
                    @endif
                    <button class="btn btn-outline btn-sm">{{ __('messages.save') }}</button>
                </form>
            </div>
        @endif

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
            $guruParticipationGroups = [
                'hadir' => $allParticipations->filter(fn ($participation) => $participation->status?->code === 'HADIR')->values(),
                'tidak_hadir' => $allParticipations->filter(fn ($participation) => $participation->status?->code === 'TIDAK_HADIR')->values(),
                'menunggu' => $allParticipations->filter(fn ($participation) => blank($participation->program_status_id))->values(),
            ];
            $guruParticipationGroupStyles = [
                'hadir' => [
                    'label' => 'text-emerald-700',
                    'count' => 'bg-emerald-100 text-emerald-700',
                ],
                'tidak_hadir' => [
                    'label' => 'text-rose-700',
                    'count' => 'bg-rose-100 text-rose-700',
                ],
                'menunggu' => [
                    'label' => 'text-amber-700',
                    'count' => 'bg-amber-100 text-amber-700',
                ],
            ];
            $adminParticipationTabs = [
                'pending' => [
                    'label' => 'Menunggu Semakan',
                    'count' => $adminPendingReviewParticipations->count(),
                ],
                'complete' => [
                    'label' => 'Complete',
                    'count' => $adminCompletedParticipations->count(),
                ],
            ];
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
        @if($canUpdateOwn)
            <section class="space-y-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h3 class="ml-[20px] text-sm font-black text-slate-900">Guru Terlibat</h3>
                        <p class="ml-[20px] text-xs text-slate-500">Paparan ringkas ikut status</p>
                    </div>
                </div>

                <div class="grid gap-4 lg:grid-cols-3">
                    @foreach([
                        'hadir' => ['label' => 'Hadir', 'count' => $guruParticipationGroups['hadir']->count()],
                        'tidak_hadir' => ['label' => 'Tidak Hadir', 'count' => $guruParticipationGroups['tidak_hadir']->count()],
                        'menunggu' => ['label' => 'Menunggu Respon', 'count' => $guruParticipationGroups['menunggu']->count()],
                    ] as $groupKey => $groupMeta)
                        <div class="rounded-3xl border border-slate-100 bg-white p-4 shadow-card">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-[0.16em] {{ $guruParticipationGroupStyles[$groupKey]['label'] }}">{{ $groupMeta['label'] }}</p>
                                    <p class="mt-1 text-lg font-black text-slate-900">{{ $groupMeta['count'] }} guru</p>
                                </div>
                                <span class="inline-flex h-9 min-w-9 items-center justify-center rounded-full px-3 text-sm font-black {{ $guruParticipationGroupStyles[$groupKey]['count'] }}">{{ $groupMeta['count'] }}</span>
                            </div>

                            <div class="mt-4 flex flex-wrap gap-2" data-testid="program-guru-group-{{ $groupKey }}">
                                @forelse($guruParticipationGroups[$groupKey] as $participation)
                                    <x-avatar :guru="$participation->guru" size="h-11 w-11" rounded="rounded-2xl" border="border border-slate-200" />
                                @empty
                                    <span class="text-xs text-slate-400">Tiada guru</span>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @else
            <section class="space-y-4" x-data="{ activeAdminTab: @js($adminPendingReviewParticipations->isNotEmpty() ? 'pending' : 'complete') }">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h3 class="ml-[20px] text-sm font-black text-slate-900">Guru Terlibat</h3>
                        <p class="ml-[20px] text-xs text-slate-500">Semakan alasan dan status program</p>
                    </div>

                    <div class="inline-flex flex-wrap gap-2 rounded-2xl bg-slate-100 p-1">
                        <button
                            type="button"
                            @click="activeAdminTab = 'pending'"
                            class="rounded-xl px-4 py-2 text-xs font-bold transition"
                            :class="activeAdminTab === 'pending' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-900'"
                        >
                            <span>Menunggu Semakan</span>
                            <span class="ml-2 inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-black text-amber-700">{{ $adminPendingReviewParticipations->count() }}</span>
                        </button>
                        <button
                            type="button"
                            @click="activeAdminTab = 'complete'"
                            class="rounded-xl px-4 py-2 text-xs font-bold transition"
                            :class="activeAdminTab === 'complete' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-900'"
                        >
                            <span>Complete</span>
                            <span class="ml-2 inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-black text-emerald-700">{{ $adminCompletedParticipations->count() }}</span>
                        </button>
                    </div>
                </div>

                <div class="rounded-3xl border border-amber-100 bg-gradient-to-br from-amber-50 via-white to-white p-4 shadow-card">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.16em] text-amber-700">Belum Hantar Respond</p>
                            <p class="mt-1 text-lg font-black text-slate-900">{{ $pendingResponseParticipations->count() }} guru</p>
                            <p class="mt-1 text-xs text-slate-500">Avatar guru yang masih belum beri maklum balas untuk program ini.</p>
                        </div>
                        <span class="inline-flex h-10 min-w-10 items-center justify-center rounded-full bg-amber-100 px-3 text-sm font-black text-amber-700">
                            {{ $pendingResponseParticipations->count() }}
                        </span>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2" data-testid="program-pending-response-avatars">
                        @forelse($pendingResponseParticipations as $participation)
                            <x-avatar :guru="$participation->guru" size="h-11 w-11" rounded="rounded-2xl" border="border border-amber-200" />
                        @empty
                            <span class="text-xs text-slate-400">Semua guru sudah hantar respond.</span>
                        @endforelse
                    </div>
                </div>

                <div x-show="activeAdminTab === 'pending'">
                    @include('programs.partials.participation-cards', [
                        'participations' => $adminPendingReviewParticipations,
                        'hideActions' => false,
                        'emptyMessage' => 'Tiada semakan yang menunggu pada masa ini.',
                    ])
                </div>

                <div x-show="activeAdminTab === 'complete'" x-cloak>
                    @include('programs.partials.participation-cards', [
                        'participations' => $adminCompletedParticipations,
                        'hideActions' => true,
                        'emptyMessage' => 'Tiada rekod complete untuk dipaparkan.',
                    ])
                </div>
            </section>
        @endif
    </div>
</x-app-layout>
