<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold">{{ __('messages.new_message') }}</h2>
                <p class="text-sm text-slate-500">Hantar mesej kepada semua guru atau guru terpilih.</p>
            </div>
            <a href="{{ route('messages.index') }}" class="btn btn-outline">{{ __('messages.inbox') }}</a>
        </div>
    </x-slot>

    <div class="card border-primary/10 bg-white/95">
        <form method="POST" action="{{ route('messages.store') }}" enctype="multipart/form-data" class="space-y-5" x-data="{ scope: '{{ old('recipient_scope', 'all') }}' }">
            @csrf

            <div class="grid gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label class="label-base">{{ __('messages.title') }}</label>
                    <input type="text" name="title" class="input-base" value="{{ old('title') }}" required>
                </div>

                <div class="md:col-span-2">
                    <label class="label-base">{{ __('messages.message') }}</label>
                    <textarea name="body" rows="5" class="input-base" required>{{ old('body') }}</textarea>
                </div>

                <div class="md:col-span-2">
                    <label class="label-base">{{ __('messages.attachment') }}</label>
                    <input type="file" name="attachment" accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip,image/*,application/pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,text/plain,text/csv,application/zip" class="file-input w-full">
                    <p class="mt-1 text-xs text-slate-500">Format: gambar, PDF, Word, Excel, PowerPoint, TXT, CSV, ZIP (maks 10MB).</p>
                </div>

                <div class="md:col-span-2 rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                    <label class="label-base">{{ __('messages.recipient') }}</label>
                    <div class="mt-2 space-y-2 text-sm">
                        <label class="flex items-center gap-2">
                            <input type="radio" name="recipient_scope" value="all" x-model="scope" @checked(old('recipient_scope', 'all') === 'all')>
                            <span>{{ __('messages.send_to_all_guru') }}</span>
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="radio" name="recipient_scope" value="selected" x-model="scope" @checked(old('recipient_scope') === 'selected')>
                            <span>{{ __('messages.send_to_selected_guru') }}</span>
                        </label>
                    </div>

                    <div class="mt-3" x-show="scope === 'selected'" x-cloak>
                        <select name="recipient_user_ids[]" multiple size="8" class="input-base">
                            @foreach($gurus as $guru)
                                @php($selected = in_array($guru->user_id, old('recipient_user_ids', [])))
                                <option value="{{ $guru->user_id }}" @selected($selected)>
                                    {{ $guru->display_name }} ({{ $guru->pasti?->name ?? '-' }})
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-slate-500">Tekan Ctrl/Command untuk pilih lebih daripada satu guru.</p>
                    </div>
                </div>
            </div>

            <div class="flex gap-2">
                <button class="btn btn-primary">{{ __('messages.send_message') }}</button>
                <a href="{{ route('messages.index') }}" class="btn btn-outline">{{ __('messages.cancel') }}</a>
            </div>
        </form>
    </div>
</x-app-layout>
