<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold">Pengumuman</h2>
        </div>
    </x-slot>

    @php
        $defaultTab = $errors->any() ? 'hantar' : 'senarai';
    @endphp

    <div x-data="{ activeTab: '{{ $defaultTab }}' }" class="space-y-4">
        <div class="card border-primary/10 bg-white/95">
            <div class="flex items-center gap-2 rounded-xl bg-slate-100 p-1">
                <button type="button" @click="activeTab = 'senarai'" class="flex-1 rounded-lg px-3 py-2 text-sm font-semibold transition" :class="activeTab === 'senarai' ? 'bg-white text-primary shadow-sm' : 'text-slate-600'">
                    Senarai Pengumuman
                </button>
                <button type="button" @click="activeTab = 'hantar'" class="flex-1 rounded-lg px-3 py-2 text-sm font-semibold transition" :class="activeTab === 'hantar' ? 'bg-white text-primary shadow-sm' : 'text-slate-600'">
                    Hantar Pengumuman
                </button>
            </div>
        </div>

        <div class="card border-primary/10 bg-white/95" x-show="activeTab === 'senarai'" x-cloak>
            <h3 class="text-base font-bold text-slate-900">Senarai Pengumuman</h3>

            <div class="mt-4 space-y-3">
                @forelse($announcements as $announcement)
                    @php
                        $isExpired = $announcement->expires_at && $announcement->expires_at->lt(now()->startOfDay());
                    @endphp
                    <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-4">
                        <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                            <div>
                                <p class="text-sm font-bold text-slate-900">{{ $announcement->title }}</p>
                                <p class="mt-1 text-sm text-slate-600 whitespace-pre-wrap">{{ $announcement->body }}</p>
                                <p class="mt-2 text-xs text-slate-500">Tarikh Tamat: {{ $announcement->expires_at?->format('d/m/Y') ?? '-' }}</p>
                                <p class="text-xs text-slate-500">Penerima: {{ $announcement->recipients_count }} guru</p>
                                <p class="text-xs text-slate-500">Dihantar oleh: {{ $announcement->sender?->display_name ?? '-' }}</p>
                                <div class="mt-2">
                                    @if($isExpired)
                                        <span class="inline-flex rounded-full bg-rose-100 px-2 py-1 text-[11px] font-bold text-rose-700">Tamat Tempoh</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-emerald-100 px-2 py-1 text-[11px] font-bold text-emerald-700">Aktif</span>
                                    @endif
                                </div>
                            </div>

                            <form method="POST" action="{{ route('announcements.destroy', $announcement) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-error btn-sm text-white" onclick="return confirm('Padam pengumuman ini?')">Padam</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="rounded-xl border-2 border-dashed border-slate-100 p-8 text-center text-slate-400">
                        Tiada pengumuman lagi.
                    </div>
                @endforelse
            </div>

            <div class="mt-4">
                {{ $announcements->links() }}
            </div>
        </div>

        <div class="card border-primary/10 bg-white/95" x-show="activeTab === 'hantar'" x-cloak>
            <h3 class="text-base font-bold text-slate-900">Hantar Pengumuman</h3>
            <form method="POST" action="{{ route('announcements.store') }}" class="mt-4 space-y-4">
                @csrf

                <div>
                    <label for="title" class="label-base">Tajuk</label>
                    <input id="title" name="title" type="text" class="input-base mt-1 block w-full" value="{{ old('title') }}" required>
                    <x-input-error class="mt-2" :messages="$errors->get('title')" />
                </div>

                <div>
                    <label for="body" class="label-base">Mesej Pengumuman</label>
                    <textarea id="body" name="body" rows="4" class="input-base mt-1 block w-full" required>{{ old('body') }}</textarea>
                    <x-input-error class="mt-2" :messages="$errors->get('body')" />
                </div>

                <div>
                    <label for="expires_at" class="label-base">Tarikh Tamat</label>
                    <input id="expires_at" name="expires_at" type="date" class="input-base mt-1 block w-full" value="{{ old('expires_at') }}" required>
                    <x-input-error class="mt-2" :messages="$errors->get('expires_at')" />
                </div>

                <div class="text-xs text-slate-500">
                    Pengumuman akan dipaparkan kepada guru sehingga tarikh tamat.
                </div>

                <div>
                    <button type="submit" class="btn btn-primary">Hantar</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
