<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold">{{ __('messages.pasti') }}</h2>
            <a href="{{ route('pasti.create') }}" class="btn btn-primary">{{ __('messages.new') }}</a>
        </div>
    </x-slot>

    @if($pastis->count())
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach($pastis as $pasti)
                <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="w-full bg-slate-100 p-2">
                        @if($pasti->image_url)
                            <a href="{{ $pasti->image_url }}" target="_blank" class="block">
                                <img src="{{ $pasti->image_url }}" alt="Gambar {{ $pasti->name }}" class="mx-auto max-h-72 w-full rounded-xl object-contain object-center">
                            </a>
                        @else
                            <div class="flex h-44 w-full items-center justify-center text-sm font-semibold text-slate-400">Tiada gambar</div>
                        @endif
                    </div>

                    <div class="space-y-3 p-4">
                        <div>
                            <h3 class="text-base font-extrabold text-slate-800">{{ $pasti->name }}</h3>
                            <p class="text-xs font-semibold uppercase tracking-wide text-primary">DUN: {{ $pasti->kawasan?->dun ?: '-' }}</p>
                        </div>

                        <div class="space-y-1.5 text-sm text-slate-600">
                            <p><span class="font-semibold text-slate-700">{{ __('messages.code') }}:</span> {{ $pasti->code ?: '-' }}</p>
                            <p><span class="font-semibold text-slate-700">{{ __('messages.phone') }}:</span> {{ $pasti->phone ?: '-' }}</p>
                            <p><span class="font-semibold text-slate-700">{{ __('messages.manager_name') }}:</span> {{ $pasti->manager_name ?: '-' }}</p>
                            <p><span class="font-semibold text-slate-700">{{ __('messages.manager_phone') }}:</span> {{ $pasti->manager_phone ?: '-' }}</p>
                        </div>

                        <div class="flex items-center gap-2 pt-1">
                            <a href="{{ route('pasti.edit', $pasti) }}" class="btn btn-outline btn-sm">{{ __('messages.edit') }}</a>
                            @role('master_admin')
                                <form method="POST" action="{{ route('pasti.destroy', $pasti) }}" class="m-0 inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-ghost btn-sm text-rose-600" onclick="return confirm('Delete?')">{{ __('messages.delete') }}</button>
                                </form>
                            @endrole
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @else
        <div class="card text-center text-slate-500">-</div>
    @endif

    <div class="mt-4">{{ $pastis->links() }}</div>
</x-app-layout>



