<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold">{{ __('messages.inbox') }}</h2>
                <p class="text-sm text-slate-500">Mesej admin dan balasan guru.</p>
            </div>
            @if($canCompose)
                <a href="{{ route('messages.create') }}" class="btn btn-primary">{{ __('messages.new_message') }}</a>
            @endif
        </div>
    </x-slot>

    <div class="space-y-3">
        @forelse($messages as $message)
            @php
                $currentRecipient = $message->recipientLinks->firstWhere('user_id', auth()->id());
                $isUnread = $isGuru && ! optional($currentRecipient)->read_at;
                $lastActivityAt = $message->replies_max_created_at ?? $message->created_at;
            @endphp
            <article class="card border-primary/10 bg-white/95">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h3 class="truncate text-lg font-bold text-slate-900">{{ $message->title }}</h3>
                        <p class="mt-1 text-xs text-slate-500">
                            {{ __('messages.from') }}: {{ $message->sender?->display_name ?? '-' }}
                            <span class="mx-2 text-slate-300">|</span>
                            {{ $lastActivityAt?->diffForHumans() }}
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        @if($isGuru)
                            <span class="badge {{ $isUnread ? 'badge-error' : 'badge-primary' }}">{{ $isUnread ? __('messages.unread') : __('messages.read') }}</span>
                        @else
                            <span class="badge badge-primary">{{ $message->recipients_count }} {{ __('messages.guru') }}</span>
                            <span class="badge badge-primary">{{ $message->replies_count }} {{ __('messages.replies') }}</span>
                        @endif
                        <a href="{{ route('messages.show', $message) }}" class="btn btn-outline btn-sm">{{ __('messages.view') }}</a>
                    </div>
                </div>

                <p class="mt-3 text-sm leading-7 text-slate-600">{{ \Illuminate\Support\Str::limit($message->body, 180) }}</p>
            </article>
        @empty
            <div class="card text-sm text-slate-500">{{ __('messages.no_messages') }}</div>
        @endforelse
    </div>

    <div class="mt-4">{{ $messages->links() }}</div>
</x-app-layout>
