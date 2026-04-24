<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold">Directory</h2>
        </div>
    </x-slot>

    @php
        $defaultTab = ($canUpload && $errors->any()) ? 'tambah' : 'senarai';
    @endphp

    <div x-data="{ activeTab: '{{ $defaultTab }}' }" class="space-y-4">
        @if($canUpload)
            <div class="card border-primary/10 bg-white/95">
                <div class="flex items-center gap-2 rounded-xl bg-slate-100 p-1">
                    <button type="button" @click="activeTab = 'senarai'" class="flex-1 rounded-lg px-3 py-2 text-sm font-semibold transition" :class="activeTab === 'senarai' ? 'bg-white text-primary shadow-sm' : 'text-slate-600'">
                        Senarai Fail
                    </button>
                    <button type="button" @click="activeTab = 'tambah'" class="flex-1 rounded-lg px-3 py-2 text-sm font-semibold transition" :class="activeTab === 'tambah' ? 'bg-white text-primary shadow-sm' : 'text-slate-600'">
                        Tambah Fail
                    </button>
                </div>
            </div>
        @endif

        <div class="card border-primary/10 bg-white/95" x-show="activeTab === 'senarai'" x-cloak>
            <h3 class="text-base font-bold text-slate-900">Senarai Fail</h3>

            <div class="mt-4 space-y-3">
                @forelse($files as $file)
                    <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-4">
                        <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                            <div>
                                <p class="text-sm font-bold text-slate-900">{{ $file->title }}</p>
                                <p class="text-xs text-slate-500">Fail asal: {{ $file->original_name }}</p>
                                <p class="text-xs text-slate-500">Muat naik oleh: {{ $file->uploader?->display_name ?? '-' }}</p>
                                <p class="text-xs text-slate-500">Tarikh: {{ $file->created_at?->format('d/m/Y h:i A') }}</p>
                                @if($file->target_type === 'all')
                                    <p class="mt-2 inline-flex rounded-full bg-emerald-100 px-2 py-1 text-[11px] font-bold text-emerald-700">Semua Guru</p>
                                @else
                                    <p class="mt-2 inline-flex rounded-full bg-amber-100 px-2 py-1 text-[11px] font-bold text-amber-700">Guru Terpilih ({{ $file->recipients->count() }})</p>
                                @endif
                            </div>

                            <div class="flex items-center gap-3">
                                @if($file->is_image_attachment && $file->file_url)
                                    <a href="{{ $file->file_url }}" target="_blank" class="block">
                                        <img src="{{ $file->file_url }}" alt="thumbnail {{ $file->title }}" class="h-16 w-16 rounded-xl border border-slate-200 object-cover">
                                    </a>
                                @endif

                                <div class="flex items-center gap-2">
                                    <a href="{{ route('directory-files.download', $file) }}" class="btn btn-outline btn-sm">Download</a>
                                    @if($canUpload && (auth()->user()->hasRole('master_admin') || (int) $file->uploaded_by === (int) auth()->id()))
                                        <form method="POST" action="{{ route('directory-files.destroy', $file) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-error btn-sm text-white" onclick="return confirm('Hapus fail ini?')">Hapus</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>

                        @if($file->target_type === 'selected' && $file->recipients->isNotEmpty() && ! $isGuruOnly)
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach($file->recipients as $recipient)
                                    <span class="rounded-full border border-slate-200 bg-white px-2 py-1 text-[11px] text-slate-600">
                                        {{ $recipient->display_name }}
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="rounded-xl border-2 border-dashed border-slate-100 p-8 text-center text-slate-400">
                        Tiada fail directory lagi.
                    </div>
                @endforelse
            </div>

            <div class="mt-4">
                {{ $files->links() }}
            </div>
        </div>

        @if($canUpload)
            <div class="card border-primary/10 bg-white/95" x-show="activeTab === 'tambah'" x-cloak>
                <h3 class="text-base font-bold text-slate-900">Tambah Fail</h3>
                <form method="POST" action="{{ route('directory-files.store') }}" enctype="multipart/form-data" class="mt-4 space-y-4" x-data="{ targetType: '{{ old('target_type', 'all') }}' }">
                    @csrf
                    <input type="hidden" name="_directory_tab" value="tambah">

                    <div>
                        <label for="title" class="label-base">Nama Fail</label>
                        <input id="title" name="title" type="text" class="input-base mt-1 block w-full" value="{{ old('title') }}" required>
                        <x-input-error class="mt-2" :messages="$errors->get('title')" />
                    </div>

                    <div>
                        <label for="attachment" class="label-base">Lampiran</label>
                        <input id="attachment" name="attachment" type="file" class="file-input w-full" required>
                        <p class="mt-1 text-xs text-slate-500">Maksimum saiz fail: 20MB</p>
                        <x-input-error class="mt-2" :messages="$errors->get('attachment')" />
                    </div>

                    <div>
                        <p class="label-base">Sasaran</p>
                        <div class="mt-2 flex flex-wrap gap-3">
                            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                <input type="radio" name="target_type" value="all" x-model="targetType">
                                <span>Semua Guru</span>
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                <input type="radio" name="target_type" value="selected" x-model="targetType">
                                <span>Guru Terpilih</span>
                            </label>
                        </div>
                        <x-input-error class="mt-2" :messages="$errors->get('target_type')" />
                    </div>

                    <div x-show="targetType === 'selected'" x-cloak>
                        <label for="guru_ids" class="label-base">Pilih Guru</label>
                        <select id="guru_ids" name="guru_ids[]" multiple class="input-base mt-1 block w-full h-48">
                            @foreach($availableGurus as $guru)
                                <option value="{{ $guru->id }}" @selected(in_array((string) $guru->id, old('guru_ids', []), true))>
                                    {{ $guru->display_name }}{{ $guru->pasti?->name ? ' - ' . $guru->pasti->name : '' }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-slate-500">Tekan `Ctrl`/`Cmd` untuk pilih lebih dari satu.</p>
                        <x-input-error class="mt-2" :messages="$errors->get('guru_ids')" />
                        <x-input-error class="mt-2" :messages="$errors->get('guru_ids.*')" />
                    </div>

                    <div x-show="targetType === 'all'" x-cloak>
                        <p class="label-base">Hantar pemberitahuan ke group Guru</p>
                        <div class="mt-2 flex flex-wrap gap-3">
                            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                <input type="radio" name="notify_group_guru" value="1" @checked((string) old('notify_group_guru', '1') === '1')>
                                <span>On</span>
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                <input type="radio" name="notify_group_guru" value="0" @checked((string) old('notify_group_guru') === '0')>
                                <span>Off</span>
                            </label>
                        </div>
                        <x-input-error class="mt-2" :messages="$errors->get('notify_group_guru')" />
                    </div>

                    <div>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        @endif
    </div>
</x-app-layout>
