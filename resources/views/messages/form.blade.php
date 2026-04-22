<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold">{{ __('messages.new_conversation') }}</h2>
                <p class="text-sm text-slate-500">
                    {{ $isGuru ? __('messages.guru_conversation_hint') : __('messages.admin_conversation_hint') }}
                </p>
            </div>
            <a href="{{ route('messages.index') }}" class="btn btn-outline">{{ __('messages.inbox') }}</a>
        </div>
    </x-slot>

    <div class="card border-primary/10 bg-white/95">
        <form method="POST" action="{{ route('messages.store') }}" enctype="multipart/form-data" class="space-y-5"
            x-data="{
                type: '{{ old('conversation_type', 'direct') }}',
                scope: '{{ old('recipient_scope', 'all') }}',
                selectedRecipients: @js(array_map('strval', old('recipient_user_ids', []))),
                tokenFeatureEnabled() {
                    if (! {{ $isAdminComposer ? 'true' : 'false' }}) {
                        return false;
                    }

                    if (this.type !== 'bulk') {
                        return false;
                    }

                    return this.scope === 'all' || this.selectedRecipients.length > 1;
                }
            }">
            @csrf

            @if($isAdminComposer)
                <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                    <label class="label-base">{{ __('messages.conversation_type') }}</label>
                    <div class="mt-2 space-y-2 text-sm">
                        <label class="flex items-center gap-2">
                            <input type="radio" name="conversation_type" value="direct" x-model="type" @checked(old('conversation_type', 'direct') === 'direct')>
                            <span>{{ __('messages.direct_conversation') }}</span>
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="radio" name="conversation_type" value="bulk" x-model="type" @checked(old('conversation_type') === 'bulk')>
                            <span>{{ __('messages.bulk_conversation') }}</span>
                        </label>
                    </div>

                    <div class="mt-4" x-show="type === 'direct'" x-cloak>
                        <label class="label-base">{{ __('messages.recipient') }}</label>
                        <select name="recipient_user_id" class="input-base">
                            <option value="">{{ __('messages.select_guru') }}</option>
                            @foreach($gurus as $guru)
                                <option value="{{ $guru->user_id }}" @selected((string) old('recipient_user_id') === (string) $guru->user_id)>
                                    {{ $guru->display_name }} ({{ $guru->pasti?->name ?? '-' }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mt-4" x-show="type === 'bulk'" x-cloak>
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
                            <select name="recipient_user_ids[]" multiple size="8" class="input-base" x-model="selectedRecipients">
                                @foreach($gurus as $guru)
                                    <option value="{{ $guru->user_id }}" @selected(in_array($guru->user_id, old('recipient_user_ids', [])))>
                                        {{ $guru->display_name }} ({{ $guru->pasti?->name ?? '-' }})
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-slate-500">{{ __('messages.multi_select_hint') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <div x-data="messageComposer(@js(old('body', '')))" class="relative">
                <label class="label-base">{{ __('messages.message') }}</label>
                <div class="relative">
                    <textarea x-ref="textarea" name="body" rows="6" class="input-base pr-14 pb-12" placeholder="{{ __('messages.write_message_hint') }}" x-model="body">{{ old('body') }}</textarea>
                    <button
                        type="button"
                        class="absolute bottom-3 right-3 inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 bg-white text-lg shadow-sm transition hover:border-primary/30 hover:text-primary"
                        @click="emojiOpen = !emojiOpen"
                    >😊</button>
                    <div
                        x-show="emojiOpen"
                        x-cloak
                        @click.outside="emojiOpen = false"
                        class="absolute bottom-14 right-0 z-20 w-64 rounded-2xl border border-slate-200 bg-white p-3 shadow-xl"
                    >
                        <p class="mb-2 text-[11px] font-bold uppercase tracking-[0.14em] text-slate-500">Pilih emoji</p>
                        <div class="grid grid-cols-6 gap-2">
                            <template x-for="emoji in emojis" :key="emoji">
                                <button
                                    type="button"
                                    class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-lg transition hover:border-primary/30 hover:bg-primary/5"
                                    @click="insertEmoji(emoji)"
                                    x-text="emoji"
                                ></button>
                            </template>
                        </div>
                    </div>
                </div>
                <input
                    id="message-attachment-input"
                    x-ref="attachmentInput"
                    type="file"
                    name="attachment"
                    accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip,image/*,application/pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,text/plain,text/csv,application/zip"
                    class="hidden"
                    @change="previewAttachment($event)"
                >
                <div x-show="attachmentName" x-cloak class="mt-3 rounded-2xl border border-slate-200 bg-slate-50/80 p-3">
                    <template x-if="attachmentIsImage && attachmentPreviewUrl">
                        <img :src="attachmentPreviewUrl" alt="Preview lampiran" class="max-h-48 rounded-2xl border border-slate-200 object-cover">
                    </template>
                    <div class="mt-2 flex items-center gap-2" :class="{ 'mt-0': !attachmentIsImage }">
                        <span class="inline-flex items-center rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-600" x-text="attachmentName"></span>
                    </div>
                </div>
                <p x-show="tokenFeatureEnabled()" x-cloak class="mt-1 text-xs text-slate-500">{{ __('messages.message_token_hint') }}</p>
                <div x-show="tokenFeatureEnabled() && hasVariableToken()" x-cloak class="mt-3 rounded-2xl border border-slate-200 bg-slate-50/80 px-3 py-2">
                    <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-slate-500">Preview pemboleh ubah</p>
                    <div class="mt-2 text-sm leading-7 text-slate-700" x-html="previewHtml()"></div>
                </div>
            </div>

            <div>
                <label class="label-base">{{ __('messages.attachment') }}</label>
                <button
                    type="button"
                    class="btn btn-outline"
                    @click="document.getElementById('message-attachment-input').click()"
                >{{ __('messages.attachment') }}</button>
                <p class="mt-1 text-xs text-slate-500">Format: gambar, PDF, Word, Excel, PowerPoint, TXT, CSV, ZIP (maks 10MB).</p>
            </div>

            <div class="flex gap-2">
                <button class="btn btn-primary">{{ __('messages.send_message') }}</button>
                <a href="{{ route('messages.index') }}" class="btn btn-outline">{{ __('messages.cancel') }}</a>
            </div>
        </form>
    </div>
    @include('messages.partials.composer-script')
</x-app-layout>
