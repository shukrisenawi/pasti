<div>
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
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
        @endif
    </div>

    @if($gurus->count())
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach($gurus as $guru)
                @php
                    $latestRequest = $latestRequests->get($guru->id);
                    $latestCompleted = $latestCompletedRequests->get($guru->id);
                    $isPending = $latestRequest && $latestRequest->completed_at === null;
                    $canGuruFill = $isGuru && (int) $guruId === (int) $guru->id && $isPending;
                @endphp

                <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    @unless($isGuru)
                        <div class="mb-2">
                            <h3 class="text-base font-extrabold text-slate-800">{{ $guru->display_name }}</h3>
                            <p class="text-xs text-slate-500">{{ __('messages.pasti') }}: {{ $guru->pasti?->name ?? '-' }}</p>
                        </div>
                    @endunless

                    <div class="mb-3 rounded-xl bg-slate-50 p-3 text-sm">
                        <p class="font-semibold text-slate-700">{{ __('messages.request_status') }}</p>
                        @if($latestRequest)
                            <p class="mt-1 font-semibold {{ $isPending ? 'text-amber-600' : 'text-emerald-700' }}">
                                {{ $isPending ? __('messages.pending') : __('messages.completed') }}
                            </p>
                            <p class="text-xs text-slate-500">{{ __('messages.requested_at_label') }}: {{ optional($latestRequest->requested_at)->format('d/m/Y H:i') }}</p>
                            @if($latestRequest->completed_at)
                                <p class="text-xs text-slate-500">{{ __('messages.completed_at_label') }}: {{ optional($latestRequest->completed_at)->format('d/m/Y H:i') }}</p>
                            @endif
                        @else
                            <p class="mt-1 text-slate-500">-</p>
                        @endif
                    </div>

                    <div class="space-y-1 text-sm text-slate-600">
                        <p class="font-semibold text-slate-700">{{ __('messages.current_info') }}</p>
                        @if($latestCompleted)
                            <p>{{ __('messages.gaji') }}: RM {{ number_format((float) $latestCompleted->gaji, 2) }}</p>
                            <p>{{ __('messages.elaun') }}: RM {{ number_format((float) $latestCompleted->elaun, 2) }}</p>
                            <p class="text-xs text-slate-500">{{ __('messages.updated_at_label') }}: {{ optional($latestCompleted->completed_at)->format('d/m/Y H:i') }}</p>
                        @else
                            <p>-</p>
                        @endif
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        @if($canGuruFill)
                            <a href="{{ route('guru-salary-information.edit', $latestRequest) }}" wire:navigate class="btn btn-primary btn-sm">
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
