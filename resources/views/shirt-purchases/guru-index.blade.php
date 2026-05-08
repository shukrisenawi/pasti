<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-bold">Pembelian Baju</h2>
    </x-slot>

    @php
        $shirtPurchaseSuccessMessage = session('shirt_purchase_success_actor') === 'guru'
            ? session('shirt_purchase_success_message')
            : null;
    @endphp

    @if(filled($shirtPurchaseSuccessMessage))
        <div data-testid="shirt-purchase-success-alert" hidden>{{ $shirtPurchaseSuccessMessage }}</div>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                if (! window.Swal) {
                    return;
                }

                window.Swal.fire({
                    icon: 'success',
                    title: @js($shirtPurchaseSuccessMessage),
                    timer: 1700,
                    showConfirmButton: false,
                    allowOutsideClick: true,
                });
            }, { once: true });
        </script>
    @endif

    <div class="space-y-4">
        @forelse($responses as $purchaseResponse)
            <div class="card border-primary/10 bg-white/95">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">{{ $purchaseResponse->purchase?->title ?? '-' }}</h3>
                        <p class="mt-1 whitespace-pre-wrap text-sm text-slate-600">{{ $purchaseResponse->purchase?->description ?: '-' }}</p>
                        <div class="mt-3 flex flex-wrap gap-2 text-xs">
                            @if($purchaseResponse->submitted_at)
                                <span class="rounded-full bg-sky-100 px-3 py-1 font-semibold text-sky-700">
                                    Sudah Isi
                                </span>
                            @endif
                            @if($purchaseResponse->paid_at && ! $purchaseResponse->approved_at)
                                <span class="rounded-full bg-amber-100 px-3 py-1 font-semibold text-amber-700">
                                    Maklum Dah Bayar
                                </span>
                            @endif
                            @if($purchaseResponse->approved_at)
                                <span class="rounded-full bg-emerald-100 px-3 py-1 font-semibold text-emerald-700">
                                    Dah Bayar
                                </span>
                            @endif
                        </div>
                    </div>
                    <a href="{{ route('shirt-purchases.show', $purchaseResponse->purchase) }}" class="btn btn-primary btn-sm">
                        {{ $purchaseResponse->submitted_at ? 'Lihat Maklumat' : 'Isi Maklumat' }}
                    </a>
                </div>
            </div>
        @empty
            <div class="rounded-xl border-2 border-dashed border-slate-100 p-8 text-center text-slate-400">
                Tiada pembelian baju untuk anda.
            </div>
        @endforelse
    </div>
</x-app-layout>
