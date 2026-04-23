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
