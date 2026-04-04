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
                    <div class="flex items-center gap-3">
                        <div class="group relative">
                            <a href="{{ route('users.gurus.edit', $guru) }}" class="block" aria-label="Lihat profil {{ $guru->display_name }}">
                                <x-avatar :guru="$guru" size="h-12 w-12" rounded="rounded-xl" border="border border-slate-200" />
                            </a>
                            <div class="pointer-events-none absolute left-1/2 top-full z-30 mt-2 hidden -translate-x-1/2 rounded-xl border border-slate-200 bg-white p-1 shadow-xl group-hover:block">
                                <img src="{{ $guru->avatar_url }}" alt="{{ $guru->display_name }}" class="h-[150px] w-[150px] rounded-lg object-cover">
                            </div>
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
                        <a href="{{ route('users.gurus.edit', $guru) }}" class="btn btn-ghost btn-sm text-primary">{{ __('messages.view') }}</a>
                        <a href="{{ route('users.gurus.edit', $guru) }}" class="btn btn-outline btn-sm">{{ __('messages.edit') }}</a>
                        <form method="POST" action="{{ route('users.gurus.destroy', $guru) }}" class="inline m-0">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-ghost btn-sm text-rose-600" onclick="return confirm('Delete?')">{{ __('messages.delete') }}</button>
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



