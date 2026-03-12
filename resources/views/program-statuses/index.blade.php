<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold">{{ __('messages.program_statuses') }}</h2>
            <a href="{{ route('program-statuses.create') }}" class="btn btn-primary">{{ __('messages.new') }}</a>
        </div>
    </x-slot>

    <div class="table-wrap">
        <table class="table-base">
            <thead><tr><th>{{ __('messages.name') }}</th><th>{{ __('messages.code') }}</th><th>{{ __('messages.status') }}</th><th>{{ __('messages.actions') }}</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($statuses as $status)
                <tr>
                    <td>{{ $status->name }}</td>
                    <td>{{ $status->code }}</td>
                    <td>{{ $status->is_hadir ? __('messages.total_hadir') : '-' }}</td>
                    <td class="space-x-2">
                        <a href="{{ route('program-statuses.edit', $status) }}" class="btn btn-outline">{{ __('messages.edit') }}</a>
                        <form method="POST" action="{{ route('program-statuses.destroy', $status) }}" class="inline">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-error" onclick="return confirm('Delete?')">{{ __('messages.delete') }}</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center">-</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $statuses->links() }}</div>
</x-app-layout>
