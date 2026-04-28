<div>
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <input
            type="text"
            wire:model.live.debounce.300ms="search"
            placeholder="{{ __('messages.search') }}..."
            class="input-base w-full max-w-sm"
        >

        @if($canRequest)
            <form method="POST" action="{{ route('pasti-information.request-all') }}">
                @csrf
                <button class="btn btn-outline" @disabled(! $canRequestAll)>
                    {{ __('messages.request_latest_pasti_info_all') }}
                </button>
            </form>

            <form method="POST" action="{{ route('pasti-information.request-reminder') }}">
                @csrf
                <button class="btn btn-outline" @disabled(! ($hasPendingRequests ?? false))>
                    Minta respon
                </button>
            </form>

            <form method="POST" action="{{ route('pasti-information.send-thanks') }}">
                @csrf
                <button class="btn btn-outline" @disabled(! ($canSendThanks ?? false))>
                    Ucapan terima kasih
                </button>
            </form>
        @endif
    </div>

    @if($pastis->count())
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach($pastis as $pasti)
                @php
                    $latestRequest = $latestRequests->get($pasti->id);
                    $latestCompleted = $latestCompletedRequests->get($pasti->id);
                    $isPending = $latestRequest && $latestRequest->completed_at === null;
                    $canGuruFill = $isGuru && (int) $guruPastiId === (int) $pasti->id && $isPending;
                @endphp

                <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    @unless($isGuru)
                        <div class="mb-2">
                            <h3 class="text-base font-extrabold text-slate-800">{{ $pasti->name }}</h3>
                            <p class="text-xs text-slate-500">{{ __('messages.kawasan') }}: {{ $pasti->kawasan?->name ?? '-' }}</p>
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
                            <p>{{ __('messages.total_guru') }}: {{ $latestCompleted->jumlah_guru ?? 0 }}</p>
                            <p>{{ __('messages.total_assistant_teacher') }}: {{ $latestCompleted->jumlah_pembantu_guru ?? 0 }}</p>
                            <p>4 {{ __('messages.year') }} (L/P): {{ $latestCompleted->murid_lelaki_4_tahun ?? 0 }}/{{ $latestCompleted->murid_perempuan_4_tahun ?? 0 }}</p>
                            <p>5 {{ __('messages.year') }} (L/P): {{ $latestCompleted->murid_lelaki_5_tahun ?? 0 }}/{{ $latestCompleted->murid_perempuan_5_tahun ?? 0 }}</p>
                            <p>6 {{ __('messages.year') }} (L/P): {{ $latestCompleted->murid_lelaki_6_tahun ?? 0 }}/{{ $latestCompleted->murid_perempuan_6_tahun ?? 0 }}</p>
                            <p class="text-xs text-slate-500">{{ __('messages.updated_at_label') }}: {{ optional($latestCompleted->completed_at)->format('d/m/Y H:i') }}</p>
                        @else
                            <p>-</p>
                        @endif
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        @if($canGuruFill)
                            <a href="{{ route('pasti-information.edit', $latestRequest) }}" wire:navigate class="btn btn-primary btn-sm">
                                {{ __('messages.fill_pasti_info') }}
                            </a>
                        @endif

                        @if($isGuru && (int) $guruPastiId === (int) $pasti->id && $latestCompleted && ! $isPending)
                            <span class="text-xs font-medium text-emerald-700">
                                {{ __('messages.pasti_info_already_completed') }}
                            </span>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    @else
        <div class="card text-center text-slate-500">-</div>
    @endif

    <div class="mt-4">{{ $pastis->links() }}</div>
</div>
