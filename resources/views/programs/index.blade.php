<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold">{{ __('messages.programs') }}</h2>
            @role('master_admin|admin')
                <a href="{{ route('programs.create') }}" class="btn btn-primary">{{ __('messages.new') }}</a>
            @endrole
        </div>
    </x-slot>

    <div class="table-wrap">
        <table class="table-base">
            <thead>
            <tr>
                <th>{{ __('messages.title') }}</th>
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
                    <td>{{ $program->program_date?->format('d/m/Y') }}</td>
                    <td>{{ $program->program_time?->format('H:i') ?? '-' }}</td>
                    <td>{{ $program->location ?? '-' }}</td>
                    <td class="space-x-2">
                        <a href="{{ route('programs.show', $program) }}" class="btn btn-outline">{{ __('messages.view') }}</a>
                        @role('master_admin|admin')
                        <a href="{{ route('programs.edit', $program) }}" class="btn btn-outline">{{ __('messages.edit') }}</a>
                        <form method="POST" action="{{ route('programs.destroy', $program) }}" class="inline">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-error" onclick="return confirm('Delete?')">{{ __('messages.delete') }}</button>
                        </form>
                        @endrole
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center">-</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $programs->links() }}</div>
</x-app-layout>
