<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-bold">Pembelian Baju</h2>
    </x-slot>

    @if(isset($purchases))
        @php($defaultTab = $errors->any() ? 'cipta' : 'senarai')
        <div x-data="{ activeTab: '{{ $defaultTab }}' }" class="space-y-4">
            <div class="card border-primary/10 bg-white/95">
                <div class="flex items-center gap-2 rounded-xl bg-slate-100 p-1">
                    <button type="button" @click="activeTab = 'senarai'" class="flex-1 rounded-lg px-3 py-2 text-sm font-semibold transition" :class="activeTab === 'senarai' ? 'bg-white text-primary shadow-sm' : 'text-slate-600'">
                        Senarai Pembelian
                    </button>
                    <button type="button" @click="activeTab = 'cipta'" class="flex-1 rounded-lg px-3 py-2 text-sm font-semibold transition" :class="activeTab === 'cipta' ? 'bg-white text-primary shadow-sm' : 'text-slate-600'">
                        Cipta Pembelian
                    </button>
                </div>
            </div>

            <div class="card border-primary/10 bg-white/95" x-show="activeTab === 'senarai'" x-cloak>
                <h3 class="text-base font-bold text-slate-900">Senarai Pembelian</h3>
                <div class="mt-4 space-y-3">
                    @forelse($purchases as $purchase)
                        <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-4">
                            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                <div>
                                    <h4 class="text-base font-bold text-slate-900">{{ $purchase->title }}</h4>
                                    <p class="mt-1 whitespace-pre-wrap text-sm text-slate-600">{{ $purchase->description ?: '-' }}</p>
                                    <div class="mt-3 flex flex-wrap gap-2 text-xs">
                                        <span class="rounded-full bg-slate-200 px-3 py-1 font-semibold text-slate-700">Sasaran: {{ $purchase->responses_count }}</span>
                                        <span class="rounded-full bg-sky-100 px-3 py-1 font-semibold text-sky-700">Isi saiz: {{ $purchase->submitted_count }}</span>
                                        <span class="rounded-full bg-amber-100 px-3 py-1 font-semibold text-amber-700">Maklum bayar: {{ $purchase->payment_notice_count }}</span>
                                        <span class="rounded-full bg-rose-100 px-3 py-1 font-semibold text-rose-700">Perlu sahkan bayaran: {{ $purchase->pending_confirmation_count }}</span>
                                        <span class="rounded-full bg-emerald-100 px-3 py-1 font-semibold text-emerald-700">Bayar sah: {{ $purchase->approved_count }}</span>
                                    </div>
                                </div>

                                <a href="{{ route('shirt-purchases.show', $purchase) }}" class="btn btn-primary btn-sm">Lihat Senarai</a>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-xl border-2 border-dashed border-slate-100 p-8 text-center text-slate-400">
                            Tiada pembelian baju lagi.
                        </div>
                    @endforelse
                </div>

                <div class="mt-4">
                    {{ $purchases->links() }}
                </div>
            </div>

            <div class="card border-primary/10 bg-white/95" x-show="activeTab === 'cipta'" x-cloak>
                <h3 class="text-base font-bold text-slate-900">Cipta Pembelian Baju</h3>
                <form method="POST" action="{{ route('shirt-purchases.store') }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label for="title" class="label-base">Tajuk</label>
                        <input id="title" name="title" type="text" class="input-base mt-1 block w-full" value="{{ old('title') }}" required>
                        <x-input-error class="mt-2" :messages="$errors->get('title')" />
                    </div>

                    <div>
                        <label for="description" class="label-base">Keterangan</label>
                        <textarea id="description" name="description" rows="4" class="input-base mt-1 block w-full">{{ old('description') }}</textarea>
                        <x-input-error class="mt-2" :messages="$errors->get('description')" />
                    </div>

                    <div>
                        <label for="image" class="label-base">Gambar Baju</label>
                        <input id="image" name="image" type="file" accept=".jpg,.jpeg,.png,.webp,image/*" class="file-input mt-1 w-full">
                        <p class="mt-1 text-xs text-slate-500">Format: JPG, PNG, WEBP (maks 7MB).</p>
                        <x-input-error class="mt-2" :messages="$errors->get('image')" />
                    </div>

                    <button class="btn btn-primary">Simpan dan Hantar</button>
                </form>
            </div>
        </div>
    @endif

</x-app-layout>
