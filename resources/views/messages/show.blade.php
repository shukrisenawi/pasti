<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold">{{ $message->title }}</h2>
                <p class="text-sm text-slate-500">
                    {{ __('messages.from') }}: {{ $message->sender?->display_name ?? '-' }}
                    <span class="mx-2 text-slate-300">|</span>
                    {{ $message->created_at?->format('d/m/Y H:i') }}
                </p>
            </div>
            <a href="{{ route('messages.index') }}" class="btn btn-outline">{{ __('messages.inbox') }}</a>
        </div>
    </x-slot>

    <section class="space-y-4">
        <article class="card border-primary/10 bg-white/95">
            <p class="whitespace-pre-line text-sm leading-7 text-slate-700">{{ $message->body }}</p>

            @if($message->attachment_url)
                @if($message->is_image_attachment)
                    <a href="{{ $message->attachment_url }}" target="_blank" rel="noopener" class="mt-4 inline-block">
                        <img src="{{ $message->attachment_url }}" alt="Message attachment" class="max-h-80 rounded-2xl border border-slate-200 object-cover">
                    </a>
                @else
                    <div class="mt-4">
                        <a href="{{ $message->attachment_url }}" target="_blank" rel="noopener" class="btn btn-outline btn-sm">
                            {{ __('messages.attachment') }}: {{ $message->attachment_name ?? 'download' }}
                        </a>
                    </div>
                @endif
            @endif

            @if($canViewRecipients)
                <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">{{ __('messages.recipient') }}</p>
                    <p class="mt-2 text-sm text-slate-600">
                        {{ $message->sent_to_all ? __('messages.send_to_all_guru') : __('messages.send_to_selected_guru') }}
                    </p>
                    <p class="mt-1 text-sm text-slate-600">{{ $message->recipients->pluck('display_name')->implode(', ') ?: '-' }}</p>
                </div>
            @endif
        </article>

        <article class="card border-primary/10 bg-white/95">
            <h3 class="text-base font-bold text-slate-900">{{ __('messages.replies') }}</h3>

            <div class="mt-4 space-y-3">
                @forelse($message->replies as $reply)
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4">
                        <div class="flex items-center gap-3">
                            <x-avatar :user="$reply->sender" size="h-10 w-10" rounded="rounded-xl" />
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-900">{{ $reply->sender?->display_name ?? '-' }}</p>
                                <p class="text-xs text-slate-500">{{ $reply->created_at?->format('d/m/Y H:i') }}</p>
                            </div>
                        </div>

                        @if($reply->body !== '')
                            <p class="mt-3 whitespace-pre-line text-sm leading-7 text-slate-700">{{ $reply->body }}</p>
                        @endif

                        @if($reply->attachment_url)
                            @if($reply->is_image_attachment)
                                <a href="{{ $reply->attachment_url }}" target="_blank" rel="noopener" class="mt-3 inline-block">
                                    <img src="{{ $reply->attachment_url }}" alt="Reply attachment" class="max-h-72 rounded-2xl border border-slate-200 object-cover">
                                </a>
                            @else
                                <div class="mt-3">
                                    <a href="{{ $reply->attachment_url }}" target="_blank" rel="noopener" class="btn btn-outline btn-sm">
                                        {{ __('messages.attachment') }}: {{ $reply->attachment_name ?? 'download' }}
                                    </a>
                                </div>
                            @endif
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-slate-500">{{ __('messages.no_replies') }}</p>
                @endforelse
            </div>
        </article>

        @if($canReply)
            <article class="card border-primary/10 bg-white/95">
                <h3 class="text-base font-bold text-slate-900">{{ __('messages.reply') }}</h3>
                <form method="POST" action="{{ route('messages.reply', $message) }}" enctype="multipart/form-data" class="mt-4 space-y-3">
                    @csrf
                    <textarea name="body" rows="4" class="input-base" placeholder="{{ __('messages.write_reply') }}">{{ old('body') }}</textarea>
                    <input type="file" name="attachment" accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip,image/*,application/pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,text/plain,text/csv,application/zip" class="file-input w-full">
                    <div class="flex gap-2">
                        <button class="btn btn-primary">{{ __('messages.send_reply') }}</button>
                    </div>
                </form>
            </article>
        @endif
    </section>
</x-app-layout>
