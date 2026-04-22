<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold">{{ __('messages.inbox') }}</h2>
                <p class="text-sm text-slate-500">{{ __('messages.conversation_list_hint') }}</p>
            </div>
            @if($canCompose)
                <a href="{{ route('messages.create') }}" class="btn btn-primary">{{ __('messages.new_conversation') }}</a>
            @endif
        </div>
    </x-slot>

    <div class="space-y-3">
        @forelse($messages as $message)
            @php
                $currentRecipient = $message->recipientLinks->firstWhere('user_id', auth()->id());
                $isUnread = $currentRecipient && ! $currentRecipient->read_at;
                $lastActivityAt = $message->replies_max_created_at ?? $message->created_at;
                $avatarUser = $message->isBulkConversation()
                    ? $message->sender
                    : $message->participants()->first(fn ($participant) => (int) $participant->id !== (int) auth()->id());
                if (is_string($lastActivityAt) && $lastActivityAt !== '') {
                    $lastActivityAt = \Illuminate\Support\Carbon::parse($lastActivityAt);
                }
            @endphp
            <article class="card border-primary/10 bg-white/95">
                <a href="{{ route('messages.show', $message) }}" class="block">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex min-w-0 flex-1 items-start gap-3">
                            <x-avatar :user="$avatarUser" size="h-12 w-12" rounded="rounded-2xl" border="border border-slate-200/80" />
                            <div class="flex flex-wrap items-center gap-2">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="truncate text-base font-bold text-slate-900">{{ $message->conversationTitleFor(auth()->user()) }}</h3>
                                    <span class="badge {{ $message->isBulkConversation() ? 'badge-primary' : 'badge-outline' }}">
                                        {{ $message->isBulkConversation() ? __('messages.bulk_conversation') : __('messages.direct_conversation') }}
                                    </span>
                                    @if($isUnread)
                                        <span class="badge badge-error">{{ __('messages.unread') }}</span>
                                    @endif
                                </div>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $message->sender?->display_name ?? '-' }} · {{ $lastActivityAt?->diffForHumans() }}
                                </p>
                                <p class="mt-3 text-sm leading-6 text-slate-600">{{ \Illuminate\Support\Str::limit($message->latestPreview(), 160) }}</p>
                            </div>
                        </div>

                        <div class="text-right text-xs text-slate-500">
                            <p>{{ $message->recipients_count }} {{ __('messages.participants') }}</p>
                            <p class="mt-1">{{ $message->replies_count }} {{ __('messages.replies') }}</p>
                        </div>
                    </div>
                </a>
            </article>
        @empty
            <div class="card text-sm text-slate-500">{{ __('messages.no_messages') }}</div>
        @endforelse
    </div>

    <div class="mt-4">{{ $messages->links() }}</div>
</x-app-layout>
