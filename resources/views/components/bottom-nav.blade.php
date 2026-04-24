@role('guru')
<div class="guru-bottom-nav fixed bottom-0 left-0 right-0 z-[100] border-t border-slate-200 bg-white/90 backdrop-blur-xl lg:hidden">
    <div class="mx-auto flex max-w-lg items-center justify-around px-2">
        <a href="{{ route('dashboard') }}" wire:navigate class="flex flex-col items-center gap-1 px-3 py-2 text-xs font-bold transition-colors {{ request()->routeIs('dashboard') ? 'text-primary' : 'text-slate-500' }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            <span>Home</span>
        </a>

        <a href="{{ route('messages.index') }}" wire:navigate class="flex flex-col items-center gap-1 px-3 py-2 text-xs font-bold transition-colors {{ request()->routeIs('messages.*') ? 'text-primary' : 'text-slate-500' }}">
            <div class="relative">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                </svg>
                @php($unreadMessages = auth()->user()->unreadInboxMessagesCount())
                @if($unreadMessages > 0)
                    <span class="absolute -right-1 -top-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[10px] text-white">{{ $unreadMessages }}</span>
                @endif
            </div>
            <span>Inbox</span>
        </a>

        <a href="{{ route('programs.index') }}" wire:navigate class="flex flex-col items-center gap-1 px-3 py-2 text-xs font-bold transition-colors {{ request()->routeIs('programs.*') ? 'text-primary' : 'text-slate-500' }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <span>Program</span>
        </a>

        <a href="{{ route('leave-notices.index') }}" wire:navigate class="flex flex-col items-center gap-1 px-3 py-2 text-xs font-bold transition-colors {{ request()->routeIs('leave-notices.*') ? 'text-primary' : 'text-slate-500' }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            <span>Cuti</span>
        </a>

        <a href="{{ route('claims.index') }}" wire:navigate class="flex flex-col items-center gap-1 px-3 py-2 text-xs font-bold transition-colors {{ request()->routeIs('claims.*') ? 'text-primary' : 'text-slate-500' }}">
            <div class="relative">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                @php($pendingClaims = auth()->user()->pending_claims_count)
                @if($pendingClaims > 0)
                    <span class="absolute -right-1 -top-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[10px] text-white">{{ $pendingClaims }}</span>
                @endif
            </div>
            <span>Claim</span>
        </a>
    </div>
</div>
@endrole
