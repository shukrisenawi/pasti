<x-app-layout>
    <x-slot name="header">
        @php
            $authUser = auth()->user();
            $isGuruOnly = $authUser->hasRole('guru') && ! $authUser->hasAnyRole(['master_admin', 'admin']);
            $onlineThreshold = now()->subMinutes(5);
            $broadcastPreviewCount = 24;
            $participants = $message->participants()
                ->filter(function ($participant) {
                    if (! $participant || ! $participant->hasRole('guru')) {
                        return true;
                    }

                    return filled($participant->guru?->pasti_id);
                })
                ->sortByDesc(function ($participant) {
                    $lastLoginAt = $participant?->last_login_at;

                    return $lastLoginAt && $lastLoginAt->gte(now()->subMinutes(5));
                })
                ->values();
            $isBroadcastToAll = $message->sent_to_all;
            $participantsSummary = $isBroadcastToAll
                ? 'Hebahan kepada semua guru'
                : $participants->pluck('display_name')->implode(', ');
            $mobileSectionMinHeight = $isGuruOnly
                ? 'min-h-[calc(100dvh-9.5rem)]'
                : 'min-h-[calc(100dvh-5rem)]';
            $mobileSectionPaddingBottom = $isGuruOnly
                ? 'pb-[calc(9.5rem+env(safe-area-inset-bottom))]'
                : 'pb-[calc(5.75rem+env(safe-area-inset-bottom))]';
            $mobileScrollerHeight = $isGuruOnly
                ? 'h-[calc(100dvh-15.25rem)] min-h-[calc(100dvh-15.25rem)]'
                : 'h-[calc(100dvh-10.75rem)] min-h-[calc(100dvh-10.75rem)]';
            $mobileComposerPosition = $isGuruOnly
                ? 'bottom-[calc(4.5rem+env(safe-area-inset-bottom))] pb-3'
                : 'bottom-0 pb-[calc(env(safe-area-inset-bottom)+0.75rem)]';
        @endphp
        <div class="hidden lg:flex lg:flex-wrap lg:items-center lg:justify-between lg:gap-3">
            <div>
                <h2 class="text-lg font-bold">{{ $message->conversationTitleFor(auth()->user()) }}</h2>
                <p class="text-sm text-slate-500">
                    {{ $message->isBulkConversation() ? __('messages.bulk_conversation') : __('messages.direct_conversation') }}
                    <span class="mx-2 text-slate-300">|</span>
                    {{ $participantsSummary }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('messages.index') }}" class="btn btn-outline">{{ __('messages.inbox') }}</a>
            </div>
        </div>
    </x-slot>

    <section class="flex {{ $mobileSectionMinHeight }} flex-col gap-0 {{ $mobileSectionPaddingBottom }} lg:grid lg:min-h-0 lg:gap-4 lg:pb-0 {{ $message->isBulkConversation() ? 'lg:grid-cols-[minmax(0,1fr)_280px]' : '' }}">
        <div class="flex flex-1 flex-col space-y-0 lg:space-y-4">
            <article
                class="-mx-4 flex flex-1 flex-col border-y border-slate-200 bg-slate-50/70 sm:-mx-6 lg:mx-0 lg:block lg:flex-none lg:rounded-3xl lg:border lg:border-primary/10 lg:bg-slate-50/80 lg:p-6"
                x-data="{
                    init() {
                        this.$nextTick(() => {
                            this.scrollToLatest();

                            this.$refs.chatScroller.querySelectorAll('img').forEach((image) => {
                                if (image.complete) {
                                    return;
                                }

                                image.addEventListener('load', () => this.scrollToLatest(), { once: true });
                            });
                        });
                    },
                    scrollToLatest() {
                        const scroller = this.$refs.chatScroller;

                        if (! scroller) {
                            return;
                        }

                        scroller.scrollTop = scroller.scrollHeight;
                    },
                }"
                x-init="init()"
            >
                <div x-ref="chatScroller" class="{{ $mobileScrollerHeight }} overflow-y-auto px-4 pt-4 pb-0 sm:px-6 lg:min-h-[400px] lg:max-h-[400px] lg:h-auto lg:px-0 lg:py-0 lg:pr-2">
                    <div class="flex min-h-full flex-col justify-start space-y-4 pb-2 lg:min-h-[400px] lg:justify-end lg:pb-0">
                        @foreach($conversationEntries as $entry)
                            @php($isMine = (int) ($entry['sender']?->id ?? 0) === (int) auth()->id())
                            <div class="flex {{ $isMine ? 'justify-end' : 'justify-start' }}" data-chat-entry>
                                <div class="max-w-2xl rounded-3xl px-4 py-3 shadow-sm {{ $entry['is_deleted'] ? 'border border-slate-200 bg-slate-100 text-slate-500' : ($isMine ? 'bg-primary text-white' : 'border border-slate-200 bg-white text-slate-800') }}">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex items-center gap-2 text-xs {{ $entry['is_deleted'] ? 'text-slate-400' : ($isMine ? 'text-white/80' : 'text-slate-500') }}">
                                            <span class="font-semibold">{{ $entry['sender']?->display_name ?? '-' }}</span>
                                            <span>•</span>
                                            <span>
                                                {{
                                                    optional($entry['created_at'])->format(
                                                        optional($entry['created_at'])->isToday()
                                                            ? 'g:i A'
                                                            : 'd/m/Y g:i A'
                                                    )
                                                }}
                                            </span>
                                        </div>
                                        @if($entry['can_delete'])
                                            <form method="POST" action="{{ $entry['delete_route'] }}" onsubmit="return confirm('Padam item mesej ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    type="submit"
                                                    class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full border {{ $entry['is_deleted'] ? 'border-slate-300 bg-white text-slate-400' : ($isMine ? 'border-white/20 bg-white/10 text-white hover:bg-white/20' : 'border-rose-200 bg-white text-rose-500 hover:bg-rose-50 hover:text-rose-600') }} transition"
                                                    title="{{ $entry['entry_type'] === 'reply' ? 'Padam balasan' : 'Padam mesej' }}"
                                                    aria-label="{{ $entry['entry_type'] === 'reply' ? 'Padam balasan ' . $entry['entry_key'] : 'Padam mesej ' . $entry['entry_key'] }}"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 6V4a1 1 0 011-1h6a1 1 0 011 1v2" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 6l-1 14a1 1 0 01-1 1H7a1 1 0 01-1-1L5 6" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 11v6M14 11v6" />
                                                    </svg>
                                                </button>
                                            </form>
                                        @endif
                                    </div>

                                    @if($entry['body'] !== '')
                                        <p class="mt-2 whitespace-pre-line text-sm leading-7 {{ $entry['is_deleted'] ? 'italic text-slate-500' : '' }}">{{ $entry['body'] }}</p>
                                    @endif

                                    @if($entry['attachment_url'])
                                        @if($entry['is_image_attachment'])
                                            <a href="{{ $entry['attachment_url'] }}" target="_blank" rel="noopener" class="mt-3 inline-block">
                                                <img src="{{ $entry['attachment_url'] }}" alt="Lampiran perbualan" class="max-h-72 rounded-2xl border border-slate-200 object-cover">
                                            </a>
                                        @else
                                            <div class="mt-3">
                                                <a href="{{ $entry['attachment_url'] }}" target="_blank" rel="noopener" class="btn btn-outline btn-sm">
                                                    {{ __('messages.attachment') }}: {{ $entry['attachment_name'] ?? 'download' }}
                                                </a>
                                            </div>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </article>

            @if($canReply)
                <article class="fixed inset-x-0 {{ $mobileComposerPosition }} z-20 border-t border-slate-200 bg-white/95 px-4 pt-3 backdrop-blur sm:px-6 lg:static lg:rounded-3xl lg:border lg:border-primary/10 lg:bg-white/95 lg:px-6 lg:py-6 lg:backdrop-blur-none" x-data="messageComposer(@js(old('body', '')))" x-init="init()">
                    <form method="POST" action="{{ route('messages.reply', $message) }}" enctype="multipart/form-data" class="space-y-3">
                        @csrf
                        <div class="relative flex items-end gap-3">
                            <input id="reply-attachment-input" x-ref="attachmentInput" type="file" name="attachment" accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip,image/*,application/pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,text/plain,text/csv,application/zip" class="hidden" @change="previewAttachment($event)">
                            <div class="relative flex-1">
                                <button
                                    type="button"
                                    class="absolute inset-y-0 left-3 inline-flex h-11 w-11 items-center justify-center self-center rounded-full text-slate-500 transition hover:bg-primary/5 hover:text-primary"
                                    @click="emojiOpen = !emojiOpen"
                                    title="Pilih emoji"
                                    aria-label="Pilih emoji"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="9" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.5 14.5c.9 1.2 2.1 1.8 3.5 1.8s2.6-.6 3.5-1.8" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 10h.01M15 10h.01" />
                                    </svg>
                                </button>
                                <input
                                    x-ref="textarea"
                                    type="text"
                                    name="body"
                                    value="{{ old('body') }}"
                                    class="input-base h-14 rounded-full border-slate-200 bg-white pl-16 pr-14 shadow-sm"
                                    placeholder="{{ __('messages.write_reply') }}"
                                    x-model="body"
                                    @focus="handleComposerFocus()"
                                >
                                <button
                                    type="button"
                                    class="absolute inset-y-0 right-2 inline-flex h-10 w-10 items-center justify-center self-center rounded-full text-slate-500 transition hover:bg-primary/5 hover:text-primary"
                                    @click="document.getElementById('reply-attachment-input').click()"
                                    title="{{ __('messages.attachment') }}"
                                    aria-label="{{ __('messages.attachment') }}"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.44 11.05l-8.49 8.49a6 6 0 11-8.49-8.49l9.19-9.19a4 4 0 115.66 5.66l-9.2 9.19a2 2 0 11-2.82-2.83l8.49-8.48" />
                                    </svg>
                                </button>
                                <div
                                    x-show="emojiOpen"
                                    x-cloak
                                    @click.outside="emojiOpen = false"
                                    class="absolute bottom-16 left-0 z-20 w-64 rounded-2xl border border-slate-200 bg-white p-3 shadow-xl"
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
                            <button
                                type="submit"
                                class="inline-flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-primary text-white shadow-lg shadow-primary/25 transition hover:bg-primary-dark"
                                title="Hantar balasan"
                                aria-label="Hantar balasan"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M3.4 20.4l17.45-7.48a1 1 0 000-1.84L3.4 3.6a.85.85 0 00-1.17.95l1.62 6.48a1 1 0 00.76.73l7.2 1.24-7.2 1.24a1 1 0 00-.76.73l-1.62 6.48a.85.85 0 001.17.95z" />
                                </svg>
                            </button>
                        </div>
                        <div x-show="attachmentName" x-cloak class="rounded-2xl border border-slate-200 bg-slate-50/80 p-3">
                            <template x-if="attachmentIsImage && attachmentPreviewUrl">
                                <img :src="attachmentPreviewUrl" alt="Preview lampiran" class="max-h-48 rounded-2xl border border-slate-200 object-cover">
                            </template>
                            <div class="mt-2 flex items-center gap-2" :class="{ 'mt-0': !attachmentIsImage }">
                                <span class="inline-flex items-center rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-600" x-text="attachmentName"></span>
                                <button
                                    type="button"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-rose-200 bg-white text-rose-500 transition hover:bg-rose-50 hover:text-rose-600"
                                    @click="clearAttachment()"
                                    title="Padam lampiran"
                                    aria-label="Padam lampiran"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 6L6 18M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </form>
                </article>
            @endif
        </div>

        @if($message->isBulkConversation())
            <aside class="hidden lg:block card border-primary/10 bg-white/95">
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">{{ __('messages.participants') }}</p>
                <div class="mt-3 space-y-3">
                    @if($isBroadcastToAll)
                        <div x-data="{ expanded: false }" class="rounded-2xl border border-slate-200 bg-slate-50/70 px-4 py-3">
                            <p class="text-sm font-semibold text-slate-900">Hebahan kepada semua guru</p>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ $participants->count() }} peserta
                            </p>
                            <div class="mt-4 grid grid-cols-4 gap-3">
                                @foreach($participants as $participant)
                                    @php($isOnline = $participant->last_login_at && $participant->last_login_at->gte($onlineThreshold))
                                    <div
                                        class="group relative"
                                        x-show="expanded || {{ $loop->index < $broadcastPreviewCount ? 'true' : 'false' }}"
                                        x-transition.opacity.duration.150ms
                                    >
                                        <div class="relative">
                                            <x-avatar :user="$participant" size="h-11 w-11" rounded="rounded-full" />
                                            @if($isOnline)
                                                <span class="absolute -bottom-0.5 -right-0.5 h-3.5 w-3.5 rounded-full border-2 border-white bg-emerald-500"></span>
                                            @endif
                                        </div>
                                        <div class="pointer-events-none absolute left-1/2 top-full z-10 mt-2 hidden w-max max-w-44 -translate-x-1/2 rounded-xl bg-slate-900 px-2.5 py-1.5 text-xs text-white shadow-lg group-hover:block">
                                            {{ $participant->display_name }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            @if($participants->count() > $broadcastPreviewCount)
                                <button
                                    type="button"
                                    class="mt-4 inline-flex items-center rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:border-primary/30 hover:text-primary"
                                    @click="expanded = !expanded"
                                    x-text="expanded ? 'Tunjuk ringkas' : 'Tunjuk semua'"
                                ></button>
                            @endif
                        </div>
                    @else
                        @foreach($participants as $participant)
                            @php($isOnline = $participant->last_login_at && $participant->last_login_at->gte($onlineThreshold))
                            <div class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50/70 px-3 py-2">
                                <div class="relative shrink-0">
                                    <x-avatar :user="$participant" size="h-10 w-10" rounded="rounded-full" />
                                    @if($isOnline)
                                        <span class="absolute -bottom-0.5 -right-0.5 h-3.5 w-3.5 rounded-full border-2 border-white bg-emerald-500"></span>
                                    @endif
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ $participant->display_name }}</p>
                                    <div class="flex items-center gap-2 text-xs text-slate-500">
                                        <span class="truncate">{{ $participant->guru?->pasti?->name ?? $participant->email ?? '-' }}</span>
                                        <span class="h-1 w-1 rounded-full bg-slate-300"></span>
                                        <span class="{{ $isOnline ? 'font-semibold text-emerald-600' : '' }}">
                                            {{ $isOnline ? 'Online' : 'Offline' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </aside>
        @endif
    </section>

    @include('messages.partials.composer-script')
</x-app-layout>
