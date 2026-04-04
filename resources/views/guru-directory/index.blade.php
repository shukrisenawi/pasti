<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-bold">Senarai Guru</h2>
    </x-slot>

    @if($gurus->count())
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach($gurus as $guru)
                <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <x-avatar :guru="$guru" size="h-11 w-11" rounded="rounded-xl" />
                        <div class="min-w-0">
                            <h3 class="truncate text-base font-extrabold text-slate-800">{{ $guru->display_name }}</h3>
                            <p class="truncate text-xs text-slate-500">{{ __('messages.pasti') }}: {{ $guru->pasti?->name ?? '-' }}</p>
                        </div>
                    </div>

                    <div class="mt-3 rounded-xl border border-slate-100 bg-slate-50 p-3">
                        <p class="text-xs font-semibold text-slate-600">No. HP</p>
                        <p class="mt-1 text-sm font-bold text-slate-800">{{ $guru->phone ?: '-' }}</p>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="mt-4">{{ $gurus->links() }}</div>
    @else
        <div class="card text-center text-slate-500">-</div>
    @endif
</x-app-layout>
