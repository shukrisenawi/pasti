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
        @endif
    </div>

    <div class="table-wrap">
        <table class="table-base">
            <thead>
            <tr>
                @unless($isGuru)
                    <th>{{ __('messages.pasti') }}</th>
                    <th>{{ __('messages.kawasan') }}</th>
                @endunless
                <th>{{ __('messages.current_info') }}</th>
                <th>{{ __('messages.request_status') }}</th>
                <th>{{ __('messages.actions') }}</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($pastis as $pasti)
                @php
                    $latestRequest = $latestRequests->get($pasti->id);
                    $latestCompleted = $latestCompletedRequests->get($pasti->id);
                    $isPending = $latestRequest && $latestRequest->completed_at === null;
                    $canGuruFill = $isGuru && (int) $guruPastiId === (int) $pasti->id && $isPending;
                    $canGuruUpdate = $isGuru && (int) $guruPastiId === (int) $pasti->id && $latestCompleted;
                @endphp
                <tr>
                    @unless($isGuru)
                        <td>{{ $pasti->name }}</td>
                        <td>{{ $pasti->kawasan?->name ?? '-' }}</td>
                    @endunless
                    <td class="text-sm">
                        @if($latestCompleted)
                            <div>{{ __('messages.total_guru') }}: {{ $latestCompleted->jumlah_guru ?? 0 }}</div>
                            <div>{{ __('messages.total_assistant_teacher') }}: {{ $latestCompleted->jumlah_pembantu_guru ?? 0 }}</div>
                            <div>4 {{ __('messages.year') }} (L/P): {{ $latestCompleted->murid_lelaki_4_tahun ?? 0 }}/{{ $latestCompleted->murid_perempuan_4_tahun ?? 0 }}</div>
                            <div>5 {{ __('messages.year') }} (L/P): {{ $latestCompleted->murid_lelaki_5_tahun ?? 0 }}/{{ $latestCompleted->murid_perempuan_5_tahun ?? 0 }}</div>
                            <div>6 {{ __('messages.year') }} (L/P): {{ $latestCompleted->murid_lelaki_6_tahun ?? 0 }}/{{ $latestCompleted->murid_perempuan_6_tahun ?? 0 }}</div>
                            <div class="mt-1 text-xs text-slate-500">
                                {{ __('messages.updated_at_label') }}: {{ optional($latestCompleted->completed_at)->format('d/m/Y H:i') }}
                            </div>
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-sm">
                        @if($latestRequest)
                            <div class="font-semibold">
                                {{ $isPending ? __('messages.pending') : __('messages.completed') }}
                            </div>
                            <div class="text-xs text-slate-500">
                                {{ __('messages.requested_at_label') }}: {{ optional($latestRequest->requested_at)->format('d/m/Y H:i') }}
                            </div>
                            @if($latestRequest->completed_at)
                                <div class="text-xs text-slate-500">
                                    {{ __('messages.completed_at_label') }}: {{ optional($latestRequest->completed_at)->format('d/m/Y H:i') }}
                                </div>
                            @endif
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        <div class="flex flex-wrap gap-2">
                            @if($canGuruFill)
                                <a href="{{ route('pasti-information.edit', $latestRequest) }}" wire:navigate class="btn btn-primary">
                                    {{ __('messages.fill_pasti_info') }}
                                </a>
                            @endif

                            @if($canGuruUpdate)
                                <a href="{{ route('pasti-information.edit', $latestCompleted) }}" wire:navigate class="btn btn-outline">
                                    {{ __('messages.update_pasti_info') }}
                                </a>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="{{ $isGuru ? 3 : 5 }}" class="text-center">-</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $pastis->links() }}</div>
</div>
