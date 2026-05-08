<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold">Pembelian Baju</h2>
                <p class="text-sm text-slate-500">{{ $purchase->title }}</p>
            </div>
            <a href="{{ route('shirt-purchases.index') }}" class="btn btn-outline btn-sm">Kembali</a>
        </div>
    </x-slot>

    <div class="card border-primary/10 bg-white/95">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h3 class="text-base font-bold text-slate-900">{{ $purchase->title }}</h3>
                <p class="mt-1 whitespace-pre-wrap text-sm text-slate-600">{{ $purchase->description ?: '-' }}</p>
                @if($purchase->image_url)
                    <a href="{{ $purchase->image_url }}" target="_blank" class="mt-3 block">
                        <img src="{{ $purchase->image_url }}" alt="{{ $purchase->title }}" class="h-40 w-full max-w-sm rounded-2xl border border-slate-200 object-cover">
                    </a>
                @endif
            </div>
            <div class="flex flex-wrap gap-2 text-xs">
                @if($response->submitted_at)
                    <span class="rounded-full bg-sky-100 px-3 py-1 font-semibold text-sky-700">
                        Sudah Isi
                    </span>
                @endif
                @if($response->paid_at)
                    <span class="rounded-full bg-emerald-100 px-3 py-1 font-semibold text-emerald-700">
                        Dah Bayar
                    </span>
                @endif
                @if($response->approved_at)
                    <span class="rounded-full bg-primary/10 px-3 py-1 font-semibold text-primary">
                        Diluluskan
                    </span>
                @endif
            </div>
        </div>

        <form method="POST" action="{{ route('shirt-purchases.responses.update', $response) }}" class="mt-4 grid gap-4 md:grid-cols-2">
            @csrf
            <div>
                <label class="label-base">Saiz</label>
                <select name="size" class="input-base mt-1 block w-full" required>
                    <option value="">- Pilih saiz -</option>
                    @foreach($sizeOptions as $size)
                        <option value="{{ $size }}" @selected(old('size', $response->size ?? $response->guru?->default_baju_size) === $size)>{{ $size }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="label-base">Kuantiti</label>
                <input type="number" min="1" max="99" name="quantity" class="input-base mt-1 block w-full" value="{{ old('quantity', $response->quantity ?: 1) }}" required>
            </div>

            <div class="md:col-span-2">
                <label class="label-base">Catatan</label>
                <textarea name="notes" rows="3" class="input-base mt-1 block w-full">{{ old('notes', $response->notes) }}</textarea>
            </div>

            <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
                <input type="checkbox" name="is_paid" value="1" class="rounded border-slate-300 text-primary" @checked(old('is_paid', $response->paid_at !== null))>
                <span>Dah bayar</span>
            </label>

            <div class="md:col-span-2">
                <button class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</x-app-layout>
