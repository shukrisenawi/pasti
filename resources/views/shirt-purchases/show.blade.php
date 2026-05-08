<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold">Senarai Pembelian Baju</h2>
                <p class="text-sm text-slate-500">{{ $purchase->title }}</p>
            </div>
            <a href="{{ route('shirt-purchases.index') }}" class="btn btn-outline btn-sm">Kembali</a>
        </div>
    </x-slot>

    <div class="space-y-4">
        <div class="card border-primary/10 bg-white/95">
            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                <div>
                    <h3 class="text-base font-bold text-slate-900">{{ $purchase->title }}</h3>
                    <p class="mt-1 whitespace-pre-wrap text-sm text-slate-600">{{ $purchase->description ?: '-' }}</p>
                    @if($purchase->image_url)
                        <a href="{{ $purchase->image_url }}" target="_blank" class="mt-3 block">
                            <img src="{{ $purchase->image_url }}" alt="{{ $purchase->title }}" class="h-48 w-full max-w-md rounded-2xl border border-slate-200 object-cover">
                        </a>
                    @endif
                </div>
                <form method="POST" action="{{ route('shirt-purchases.broadcast', $purchase) }}">
                    @csrf
                    <button class="btn btn-primary">Keluarkan Senarai</button>
                </form>
            </div>
        </div>

        <div class="grid gap-3">
            @foreach($purchase->responses->sortBy(fn ($response) => $response->guru?->display_name)->values() as $response)
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <h4 class="text-sm font-extrabold text-slate-800">{{ $response->guru?->display_name ?? '-' }}</h4>
                            <p class="text-xs text-slate-500">{{ __('messages.pasti') }}: {{ $response->guru?->pasti?->name ?? '-' }}</p>
                            <div class="mt-2 grid gap-1 text-sm text-slate-700">
                                <p>Saiz: <span class="font-bold">{{ $response->size ?? '-' }}</span></p>
                                <p>Kuantiti: <span class="font-bold">{{ $response->quantity }}</span></p>
                                <p>Catatan: <span class="font-bold">{{ $response->notes ?: '-' }}</span></p>
                            </div>
                        </div>

                        <div class="w-full max-w-xs space-y-2">
                            <div class="flex flex-wrap gap-2 text-xs">
                                <span class="rounded-full {{ $response->paid_at ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700' }} px-3 py-1 font-semibold">
                                    {{ $response->paid_at ? 'Dah Bayar' : 'Belum Bayar' }}
                                </span>
                                <span class="rounded-full {{ $response->approved_at ? 'bg-primary/10 text-primary' : 'bg-amber-100 text-amber-700' }} px-3 py-1 font-semibold">
                                    {{ $response->approved_at ? 'Diluluskan' : 'Belum Approve' }}
                                </span>
                            </div>

                            <form method="POST" action="{{ route('shirt-purchases.responses.mark-paid', $response) }}">
                                @csrf
                                <button class="btn btn-outline btn-sm w-full" @disabled($response->paid_at !== null)>Tandakan Manual Dah Bayar</button>
                            </form>

                            <form method="POST" action="{{ route('shirt-purchases.responses.approve', $response) }}">
                                @csrf
                                <button class="btn btn-primary btn-sm w-full" @disabled($response->paid_at === null || $response->approved_at !== null)>Approve Bayaran</button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>
