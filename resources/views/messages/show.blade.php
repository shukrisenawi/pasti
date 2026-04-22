<x-app-layout>
    <x-slot name="header">
        @php
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
        @endphp
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold">{{ $message->conversationTitleFor(auth()->user()) }}</h2>
                <p class="text-sm text-slate-500">
                    {{ $message->isBulkConversation() ? __('messages.bulk_conversation') : __('messages.direct_conversation') }}
                    <span class="mx-2 text-slate-300">|</span>
                    {{ $participantsSummary }}
                </p>
            </div>
            <a href="{{ route('messages.index') }}" class="btn btn-outline">{{ __('messages.inbox') }}</a>
        </div>
    </x-slot>

    <section class="grid gap-4 {{ $message->isBulkConversation() ? 'lg:grid-cols-[minmax(0,1fr)_280px]' : '' }}">
        <div class="space-y-4">
            <article class="card border-primary/10 bg-slate-50/80">
                <div class="space-y-4">
                    @foreach($conversationEntries as $entry)
                        @php($isMine = (int) ($entry['sender']?->id ?? 0) === (int) auth()->id())
                        <div class="flex {{ $isMine ? 'justify-end' : 'justify-start' }}">
                            <div class="max-w-2xl rounded-3xl px-4 py-3 shadow-sm {{ $isMine ? 'bg-primary text-white' : 'border border-slate-200 bg-white text-slate-800' }}">
                                <div class="flex items-center gap-2 text-xs {{ $isMine ? 'text-white/80' : 'text-slate-500' }}">
                                    <span class="font-semibold">{{ $entry['sender']?->display_name ?? '-' }}</span>
                                    <span>•</span>
                                    <span>{{ optional($entry['created_at'])->format('d/m/Y H:i') }}</span>
                                </div>

                                @if($entry['body'] !== '')
                                    <p class="mt-2 whitespace-pre-line text-sm leading-7">{{ $entry['body'] }}</p>
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
            </article>

            @if($canReply)
                <article class="card border-primary/10 bg-white/95" x-data="messageComposer(@js(old('body', '')))">
                    <h3 class="text-base font-bold text-slate-900">{{ __('messages.reply') }}</h3>
                    <form method="POST" action="{{ route('messages.reply', $message) }}" enctype="multipart/form-data" class="mt-4 space-y-3">
                        @csrf
                        <div class="relative">
                            <textarea x-ref="textarea" name="body" rows="4" class="input-base pr-14 pb-12" placeholder="{{ __('messages.write_reply') }}" x-model="body">{{ old('body') }}</textarea>
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
                        <input type="file" name="attachment" accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip,image/*,application/pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,text/plain,text/csv,application/zip" class="file-input w-full">
                        <div class="flex gap-2">
                            <button class="btn btn-primary">{{ __('messages.send_reply') }}</button>
                        </div>
                    </form>
                </article>
            @endif
        </div>

        @if($message->isBulkConversation())
            <aside class="card border-primary/10 bg-white/95">
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
