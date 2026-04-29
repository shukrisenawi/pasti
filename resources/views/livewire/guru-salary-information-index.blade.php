<div>
    <div class="mb-4 space-y-3">
        <div class="flex flex-wrap items-center gap-2">
            <button
                type="button"
                wire:click="switchTab('pending')"
                class="btn {{ $activeTab === 'pending' ? 'btn-primary' : 'btn-outline' }}"
            >
                Elaun guru belum respond
            </button>
            <button
                type="button"
                wire:click="switchTab('responded')"
                class="btn {{ $activeTab === 'responded' ? 'btn-primary' : 'btn-outline' }}"
            >
                Elaun guru dah respond
            </button>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="{{ __('messages.search') }}..."
                class="input-base w-full max-w-sm"
            >

            @if($canRequest)
                <form method="POST" action="{{ route('guru-salary-information.request-all') }}">
                    @csrf
                    <button class="btn btn-outline" @disabled(! $canRequestAll)>
                        {{ __('messages.request_latest_guru_salary_info_all') }}
                    </button>
                </form>

                <form method="POST" action="{{ route('guru-salary-information.request-reminder') }}">
                    @csrf
                    <button class="btn btn-outline" @disabled(! ($canRequestReminder ?? false))>
                        Minta respond
                    </button>
                </form>
            @endif
        </div>
    </div>

    @if($gurus->count())
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
            @foreach($gurus as $guru)
                @php
                    $latestRequest = $latestRequests->get($guru->id);
                    $latestCompleted = $latestCompletedRequests->get($guru->id);
                    $isPending = $latestRequest && $latestRequest->completed_at === null;
                    $canGuruFill = $isGuru && (int) $guruId === (int) $guru->id && $isPending;
                @endphp

                <article class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                    <div class="flex items-center gap-2.5">
                        <x-avatar :guru="$guru" size="h-10 w-10" rounded="rounded-lg" />
                        <div class="min-w-0">
                            <h3 class="truncate text-sm font-extrabold text-slate-800">{{ $guru->display_name }}</h3>
                            <p class="truncate text-xs text-slate-500">{{ __('messages.pasti') }}: {{ $guru->pasti?->name ?? '-' }}</p>
                        </div>
                    </div>

                    <div class="mt-3 rounded-lg border border-slate-100 bg-slate-50 p-2.5 text-xs">
                        <div class="flex items-center justify-between gap-2">
                            <p class="font-semibold text-slate-700">{{ __('messages.request_status') }}</p>
                            @if($latestRequest)
                                <span class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-bold {{ $isPending ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700' }}">
                                    {{ $isPending ? __('messages.pending') : __('messages.completed') }}
                                </span>
                            @endif
                        </div>
                        @if($latestRequest)
                            <p class="mt-1 text-slate-500">{{ __('messages.requested_at_label') }}: {{ optional($latestRequest->requested_at)->format('d/m/Y H:i') }}</p>
                            @if($latestRequest->completed_at)
                                <p class="text-slate-500">{{ __('messages.completed_at_label') }}: {{ optional($latestRequest->completed_at)->format('d/m/Y H:i') }}</p>
                            @endif
                        @else
                            <p class="mt-1 font-semibold text-slate-500">Belum dihantar</p>
                        @endif
                    </div>

                    <div class="mt-2 rounded-lg border border-slate-100 bg-white p-2.5 text-xs text-slate-600">
                        <p class="font-semibold text-slate-700">{{ __('messages.current_info') }}</p>
                        @if($latestCompleted)
                            <div class="mt-1 grid grid-cols-2 gap-2">
                                <p>{{ __('messages.gaji') }}:<br><span class="font-bold text-slate-800">RM {{ number_format((float) $latestCompleted->gaji, 2) }}</span></p>
                                <p>{{ __('messages.elaun') }}:<br><span class="font-bold text-slate-800">RM {{ number_format((float) $latestCompleted->elaun, 2) }}</span></p>
                            </div>
                            <p class="mt-1 text-slate-500">{{ __('messages.updated_at_label') }}: {{ optional($latestCompleted->completed_at)->format('d/m/Y H:i') }}</p>
                        @else
                            <p class="mt-1">-</p>
                        @endif
                    </div>

                    <div class="mt-3 flex flex-wrap gap-2">
                        @if($canGuruFill)
                            <a href="{{ route('guru-salary-information.edit', $latestRequest) }}" wire:navigate class="btn btn-primary btn-sm !px-3 !py-1.5 text-xs">
                                {{ __('messages.fill_guru_salary_info') }}
                            </a>
                        @endif

                        @if($isGuru && (int) $guruId === (int) $guru->id && $latestCompleted && ! $isPending)
                            <span class="text-xs font-medium text-emerald-700">
                                {{ __('messages.guru_salary_info_already_completed') }}
                            </span>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    @else
        <div class="card text-center text-slate-500">-</div>
    @endif

    <div class="mt-4">{{ $gurus->links() }}</div>
</div>
