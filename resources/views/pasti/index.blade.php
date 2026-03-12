<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold">{{ __('messages.pasti') }}</h2>
            <a href="{{ route('pasti.create') }}" class="btn btn-primary">{{ __('messages.new') }}</a>
        </div>
    </x-slot>

    <div class="table-wrap">
        <table class="table-base">
            <thead>
            <tr>
                <th>{{ __('messages.name') }}</th>
                <th>{{ __('messages.kawasan') }}</th>
                <th>{{ __('messages.code') }}</th>
                <th>{{ __('messages.phone') }}</th>
                <th>{{ __('messages.manager_name') }}</th>
                <th>{{ __('messages.manager_phone') }}</th>
                <th>{{ __('messages.actions') }}</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($pastis as $pasti)
                <tr>
                    <td>{{ $pasti->name }}</td>
                    <td>{{ $pasti->kawasan?->name }}</td>
                    <td>{{ $pasti->code ?: '-' }}</td>
                    <td>{{ $pasti->phone ?: '-' }}</td>
                    <td>{{ $pasti->manager_name ?: '-' }}</td>
                    <td>{{ $pasti->manager_phone ?: '-' }}</td>
                    <td class="space-x-2">
                        <a href="{{ route('pasti.edit', $pasti) }}" class="btn btn-outline">{{ __('messages.edit') }}</a>
                        @role('master_admin')
                        <form method="POST" action="{{ route('pasti.destroy', $pasti) }}" class="inline">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-error" onclick="return confirm('Delete?')">{{ __('messages.delete') }}</button>
                        </form>
                        @endrole
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center">-</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $pastis->links() }}</div>
</x-app-layout>
