<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold">{{ $message->conversationTitleFor(auth()->user()) }}</h2>
                <p class="text-sm text-slate-500">
                    {{ $message->isBulkConversation() ? __('messages.bulk_conversation') : __('messages.direct_conversation') }}
                    <span class="mx-2 text-slate-300">|</span>
                    {{ $message->participants()->pluck('display_name')->implode(', ') }}
                </p>
            </div>
            <a href="{{ route('messages.index') }}" class="btn btn-outline">{{ __('messages.inbox') }}</a>
        </div>
    </x-slot>

    <section class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_280px]">
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
                <article class="card border-primary/10 bg-white/95">
                    <h3 class="text-base font-bold text-slate-900">{{ __('messages.reply') }}</h3>
                    <form method="POST" action="{{ route('messages.reply', $message) }}" enctype="multipart/form-data" class="mt-4 space-y-3">
                        @csrf
                        <textarea name="body" rows="4" class="input-base" placeholder="{{ __('messages.write_reply') }}">{{ old('body') }}</textarea>
                        <p class="text-xs text-slate-500">{{ __('messages.message_token_hint') }}</p>
                        <input type="file" name="attachment" accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip,image/*,application/pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,text/plain,text/csv,application/zip" class="file-input w-full">
                        <div class="flex gap-2">
                            <button class="btn btn-primary">{{ __('messages.send_reply') }}</button>
                        </div>
                    </form>
                </article>
            @endif
        </div>

        <aside class="card border-primary/10 bg-white/95">
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">{{ __('messages.participants') }}</p>
            <div class="mt-3 space-y-3">
                @foreach($message->participants() as $participant)
                    <div class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50/70 px-3 py-2">
                        <x-avatar :user="$participant" size="h-10 w-10" rounded="rounded-xl" />
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-slate-900">{{ $participant->display_name }}</p>
                            <p class="truncate text-xs text-slate-500">{{ $participant->guru?->pasti?->name ?? $participant->email ?? '-' }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </aside>
    </section>
</x-app-layout>
