<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold">{{ __('messages.guru') }}</h2>
            <a href="{{ route('users.gurus.create') }}" class="btn btn-primary">{{ __('messages.new') }}</a>
        </div>
    </x-slot>

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div class="flex p-1 bg-slate-100 rounded-xl w-fit">
            <a href="{{ route('users.gurus.index', ['tab' => 'guru']) }}"
               class="px-4 py-2 rounded-lg text-sm font-semibold transition-all {{ $activeTab === 'guru' ? 'bg-white shadow-sm text-primary' : 'text-slate-500 hover:text-slate-700' }}">
                {{ __('messages.main_teacher') }}
                <span class="ml-1 opacity-60">({{ $guruCount }})</span>
            </a>
            <a href="{{ route('users.gurus.index', ['tab' => 'assistant']) }}"
               class="px-4 py-2 rounded-lg text-sm font-semibold transition-all {{ $activeTab === 'assistant' ? 'bg-white shadow-sm text-primary' : 'text-slate-500 hover:text-slate-700' }}">
                {{ __('messages.assistant_teacher') }}
                <span class="ml-1 opacity-60">({{ $assistantCount }})</span>
            </a>
        </div>

        <form method="GET" action="{{ route('users.gurus.index') }}" class="flex w-full max-w-md items-center gap-2">
            <input type="hidden" name="tab" value="{{ $activeTab }}">
            <input
                type="text"
                name="search"
                value="{{ $search }}"
                placeholder="{{ __('messages.search') }}..."
                class="input-base"
            >
            <button class="btn btn-primary" type="submit">{{ __('messages.search') }}</button>
            @if($search !== '')
                <a href="{{ route('users.gurus.index', ['tab' => $activeTab]) }}" class="btn btn-outline">{{ __('messages.cancel') }}</a>
            @endif
        </form>
    </div>

    @if($gurus->count())
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach($gurus as $guru)
                <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    @php($hasUploadedAvatar = filled($guru->avatar_path) || filled($guru->user?->avatar_path))
                    <div class="flex items-center gap-3">
                        <div class="group relative" x-data="{ menuOpen: false }">
                            <a href="{{ route('users.gurus.edit', $guru) }}" class="block" aria-label="Lihat profil {{ $guru->display_name }}">
                                <x-avatar :guru="$guru" size="h-12 w-12" rounded="rounded-xl" border="border border-slate-200" />
                            </a>
                            @if(!$guru->is_assistant)
                                <button
                                    type="button"
                                    class="absolute -right-2 -top-2 rounded-full border border-slate-200 bg-white p-1 text-slate-500 shadow-sm hover:text-primary"
                                    @click.stop="menuOpen = !menuOpen"
                                    aria-label="Menu avatar guru"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5h.01M12 12h.01M12 19h.01"/>
                                    </svg>
                                </button>
                                <div
                                    x-show="menuOpen"
                                    x-transition
                                    @click.outside="menuOpen = false"
                                    class="absolute left-1/2 top-14 z-30 min-w-[180px] -translate-x-1/2 rounded-xl border border-slate-200 bg-white p-1 shadow-xl"
                                    style="display: none;"
                                >
                                    <a
                                        href="{{ route('users.gurus.assistants', ['users_guru' => $guru, 'tab' => 'list']) }}"
                                        class="block rounded-lg px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-primary/10 hover:text-primary"
                                    >
                                        Pembantu Guru
                                    </a>
                                </div>
                            @endif
                            @if($hasUploadedAvatar)
                            <div class="pointer-events-none absolute left-full top-1/2 z-30 hidden ml-2 -translate-y-1/2 md:block md:invisible md:opacity-0 md:transition md:duration-150 md:group-hover:visible md:group-hover:opacity-100">
                                <div class="h-[150px] w-[150px] overflow-hidden rounded-xl border border-slate-200 bg-white p-1 shadow-xl"><img src="{{ $guru->avatar_url }}" alt="{{ $guru->display_name }}" class="h-full w-full rounded-lg object-cover"></div>
                            </div>
                            @endif
                        </div>
                        <div class="min-w-0">
                            <h3 class="truncate text-base font-extrabold text-slate-800">{{ $guru->display_name }}</h3>
                            <p class="truncate text-sm text-slate-500">{{ $guru->pasti?->name ?? '-' }}</p>
                        </div>
                    </div>

                    <div class="mt-4 space-y-1.5 text-sm text-slate-600">
                        <p><span class="font-semibold text-slate-700">{{ __('messages.phone') }}:</span> {{ $guru->phone ?? '-' }}</p>
                        <p>
                            <span class="font-semibold text-slate-700">{{ __('messages.status') }}:</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $guru->active ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-800' }}">
                                {{ $guru->active ? __('messages.active') : __('messages.inactive') }}
                            </span>
                        </p>
                        <p><span class="font-semibold text-slate-700">{{ __('messages.kpi_score') }}:</span> {{ number_format((float) ($guru->kpiSnapshot?->score ?? 0), 2) }}%</p>
                    </div>

                    <div class="mt-4 flex items-center gap-2">
                        <a href="{{ route('users.gurus.edit', $guru) }}" class="btn btn-ghost btn-sm h-8 w-8 p-0 text-primary" title="{{ __('messages.view') }}" aria-label="{{ __('messages.view') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7Z"/>
                                <circle cx="12" cy="12" r="3" stroke-width="2"/>
                            </svg>
                        </a>
                        <a href="{{ route('users.gurus.edit', $guru) }}" class="btn btn-outline btn-sm h-8 w-8 p-0" title="{{ __('messages.edit') }}" aria-label="{{ __('messages.edit') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 3.487a2.1 2.1 0 0 1 2.971 2.971L8.36 17.93 4 19l1.07-4.36 11.792-11.153Z"/>
                            </svg>
                        </a>
                        @if($guru->user && $guru->user->hasRole('guru'))
                            <form method="POST" action="{{ route('users.gurus.reset-password', $guru) }}" class="inline m-0">
                                @csrf
                                <button
                                    class="btn btn-ghost btn-sm h-8 w-8 p-0 text-amber-600"
                                    type="submit"
                                    title="Reset password ke 123"
                                    aria-label="Reset password ke 123"
                                    onclick="return confirm('Reset password guru ini kepada 123?')"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c1.657 0 3-1.343 3-3V6a3 3 0 10-6 0v2c0 1.657 1.343 3 3 3z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 11h14a1 1 0 011 1v7a1 1 0 01-1 1H5a1 1 0 01-1-1v-7a1 1 0 011-1z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16h8"/>
                                    </svg>
                                </button>
                            </form>
                            <form method="POST" action="{{ route('users.gurus.impersonate', $guru) }}" class="inline m-0">
                                @csrf
                                <input type="hidden" name="return_to" value="{{ url()->full() }}">
                                <button class="btn btn-ghost btn-sm h-8 w-8 p-0 text-emerald-700" type="submit" title="Masuk sebagai guru" aria-label="Masuk sebagai guru">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 7V4a1 1 0 0 1 1-1h8a1 1 0 0 1 1 1v16a1 1 0 0 1-1 1h-8a1 1 0 0 1-1-1v-3"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H3m0 0 4-4m-4 4 4 4"/>
                                    </svg>
                                </button>
                            </form>
                        @endif
                        <form method="POST" action="{{ route('users.gurus.destroy', $guru) }}" class="inline m-0">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-ghost btn-sm h-8 w-8 p-0 text-rose-600" title="{{ __('messages.delete') }}" aria-label="{{ __('messages.delete') }}" onclick="return confirm('Delete?')">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h16M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2m-8 0 1 12a1 1 0 0 0 1 .917h6a1 1 0 0 0 1-.917L17 7"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 11v6M14 11v6"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                </article>
            @endforeach
        </div>
    @else
        <div class="card text-center text-slate-500">-</div>
    @endif

    <div class="mt-4">{{ $gurus->links() }}</div>
</x-app-layout>









