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

    <div class="table-wrap">
        <table class="table-base">
            <thead>
            <tr>
                <th>{{ __('messages.title') }}</th>
                <th>{{ __('messages.markah') }}</th>
                <th>{{ __('messages.date') }}</th>
                <th>{{ __('messages.time') }}</th>
                <th>{{ __('messages.location') }}</th>
                <th>{{ __('messages.actions') }}</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($programs as $program)
                <tr>
                    <td>{{ $program->title }}</td>
                    <td>{{ $program->markah }}</td>
                    <td>{{ $program->program_date?->format('d/m/Y') }}</td>
                    <td>{{ $program->program_time?->format('H:i') ?? '-' }}</td>
                    <td>{{ $program->location ?? '-' }}</td>
                    <td class="flex items-center gap-1">
                        <a href="{{ route('programs.show', $program) }}" wire:navigate class="btn btn-ghost btn-xs btn-circle text-primary" title="{{ __('messages.view') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.644C3.399 8.049 7.306 5 12 5c4.694 0 8.601 3.049 9.964 6.678a1.012 1.012 0 010 .644C20.601 15.951 16.694 19 12 19c-4.694 0-8.601-3.049-9.964-6.678z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </a>
                        @if($canManageProgram)
                            <a href="{{ route('programs.edit', $program) }}" wire:navigate class="btn btn-ghost btn-xs btn-circle text-amber-600" title="{{ __('messages.edit') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                </svg>
                            </a>
                            <form method="POST" action="{{ route('programs.destroy', $program) }}" class="inline m-0">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-ghost btn-xs btn-circle text-rose-600" onclick="return confirm('Delete?')" title="{{ __('messages.delete') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                    </svg>
                                </button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center">-</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $programs->links() }}</div>
</div>
