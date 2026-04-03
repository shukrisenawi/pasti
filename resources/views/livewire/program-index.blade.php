<div>
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <input
            type="text"
            wire:model.live.debounce.300ms="search"
            placeholder="{{ __('messages.search') }}..."
            class="input-base w-full max-w-sm"
        >

        @if($canManageProgram)
            <a href="{{ route('programs.create') }}" wire:navigate class="btn btn-primary">{{ __('messages.new') }}</a>
        @endif
    </div>

    @if($programs->count())
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach($programs as $program)
                <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="space-y-1">
                        <h3 class="text-base font-extrabold text-slate-800">{{ $program->title }}</h3>
                        <p class="text-sm text-slate-600">{{ __('messages.location') }}: {{ $program->location ?? '-' }}</p>
                    </div>

                    <div class="mt-3 space-y-1 text-sm text-slate-600">
                        <p><span class="font-semibold text-slate-700">{{ __('messages.markah') }}:</span> {{ $program->markah }}</p>
                        <p><span class="font-semibold text-slate-700">{{ __('messages.date') }}:</span> {{ $program->program_date?->format('d/m/Y') }}</p>
                        <p><span class="font-semibold text-slate-700">{{ __('messages.time') }}:</span> {{ $program->program_time?->format('H:i') ?? '-' }}</p>
                    </div>

                    <div class="mt-4 flex items-center gap-2">
                        <a href="{{ route('programs.show', $program) }}" wire:navigate class="btn btn-ghost btn-sm text-primary">{{ __('messages.view') }}</a>
                        @if($canManageProgram)
                            <a href="{{ route('programs.edit', $program) }}" wire:navigate class="btn btn-outline btn-sm">{{ __('messages.edit') }}</a>
                            <form method="POST" action="{{ route('programs.destroy', $program) }}" class="inline m-0">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-ghost btn-sm text-rose-600" onclick="return confirm('Delete?')">{{ __('messages.delete') }}</button>
                            </form>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    @else
        <div class="card text-center text-slate-500">-</div>
    @endif

    <div class="mt-4">{{ $programs->links() }}</div>
</div>
