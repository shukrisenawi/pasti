<div>
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <input
            type="text"
            wire:model.live.debounce.300ms="search"
            placeholder="{{ __('messages.search') }}..."
            class="input-base w-full max-w-sm"
        >

        @if($canManageProgram)
            <a href="{{ route('programs.create') }}" wire:navigate class="btn btn-primary">{{ __('messages.new') }}</a>
        @endif
    </div>

    @if($programs->count())
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach($programs as $program)
                @php
                    $guruParticipation = ! $canManageProgram ? $program->participations->first() : null;
                    $needsResponse = ! $canManageProgram && blank($guruParticipation?->program_status_id);
                    $hasResponded = ! $canManageProgram && filled($guruParticipation?->program_status_id);
                    $cardClasses = 'rounded-2xl border p-4 shadow-sm transition';

                    if ($needsResponse) {
                        $cardClasses .= ' border-amber-300 bg-amber-50/80 shadow-amber-100/70 ring-1 ring-amber-200/80';
                    } elseif ($hasResponded) {
                        $cardClasses .= ' border-emerald-200 bg-emerald-50/60 shadow-emerald-100/60';
                    } else {
                        $cardClasses .= ' border-slate-200 bg-white';
                    }
                @endphp

                <article
                    class="{{ $cardClasses }}"
                    @if($needsResponse) data-testid="program-card-pending-response" @elseif($hasResponded) data-testid="program-card-responded" @endif
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="space-y-1 min-w-0">
                            <h3 class="text-base font-extrabold text-slate-800">{{ $program->title }}</h3>
                            <p class="text-sm text-slate-600">{{ __('messages.location') }}: {{ $program->location ?? '-' }}</p>
                        </div>
                        @if($needsResponse)
                            <span class="shrink-0 rounded-full bg-amber-100 px-2.5 py-1 text-[11px] font-bold text-amber-700">
                                Perlu Respon
                            </span>
                        @elseif($hasResponded)
                            <span class="shrink-0 rounded-full bg-emerald-100 px-2.5 py-1 text-[11px] font-bold text-emerald-700">
                                Sudah Respon
                            </span>
                        @endif
                        @if($canManageProgram && ($program->pending_absence_reason_approvals_count ?? 0) > 0)
                            <span data-testid="program-card-pending-badge" class="shrink-0 rounded-full bg-amber-100 px-2.5 py-1 text-[11px] font-bold text-amber-700">
                                {{ ($program->pending_absence_reason_approvals_count ?? 0) > 99 ? '99+' : ($program->pending_absence_reason_approvals_count ?? 0) }}
                            </span>
                        @endif
                    </div>

                    @if($needsResponse)
                        <p class="mt-3 rounded-xl border border-amber-200/80 bg-white/80 px-3 py-2 text-sm font-medium text-amber-800">
                            Kehadiran program ini masih belum direspon.
                        </p>
                    @endif

                    <div class="mt-3 space-y-1 text-sm text-slate-600">
                        <p><span class="font-semibold text-slate-700">{{ __('messages.markah') }}:</span> {{ $program->markah }}</p>
                        <p><span class="font-semibold text-slate-700">{{ __('messages.date') }}:</span> {{ $program->program_date?->format('d/m/Y') }}</p>
                        <p><span class="font-semibold text-slate-700">{{ __('messages.time') }}:</span> {{ $program->program_time?->format('H:i') ?? '-' }}</p>
                    </div>

                    <div class="mt-4 flex items-center gap-2">
                        <a href="{{ route('programs.show', $program) }}" wire:navigate class="btn btn-ghost btn-sm text-primary">{{ __('messages.view') }}</a>
                        @if($canManageProgram)
                            <a href="{{ route('programs.edit', $program) }}" wire:navigate class="btn btn-outline btn-sm">{{ __('messages.edit') }}</a>
                            <form method="POST" action="{{ route('programs.destroy', $program) }}" class="inline m-0">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-ghost btn-sm text-rose-600" onclick="return confirm('Delete?')">{{ __('messages.delete') }}</button>
                            </form>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    @else
        <div class="card text-center text-slate-500">-</div>
    @endif

    <div class="mt-4">{{ $programs->links() }}</div>
</div>
