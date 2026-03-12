<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold">{{ __('messages.kawasan') }}</h2>
            <a href="{{ route('kawasan.create') }}" class="btn btn-primary">{{ __('messages.new') }}</a>
        </div>
    </x-slot>

    <div class="table-wrap">
        <table class="table-base">
            <thead>
            <tr>
                <th>{{ __('messages.name') }}</th>
                <th>{{ __('messages.dun') }}</th>
                <th>{{ __('messages.actions') }}</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($kawasans as $kawasan)
                <tr>
                    <td>{{ $kawasan->name }}</td>
                    <td>{{ $kawasan->dun ?? '-' }}</td>
                    <td class="space-x-2">
                        <a class="btn btn-outline" href="{{ route('kawasan.edit', $kawasan) }}">{{ __('messages.edit') }}</a>
                        <form method="POST" action="{{ route('kawasan.destroy', $kawasan) }}" class="inline">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-error" onclick="return confirm('Delete?')">{{ __('messages.delete') }}</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="3" class="text-center">-</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $kawasans->links() }}</div>
</x-app-layout>
