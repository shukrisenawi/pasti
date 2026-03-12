<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-2">
            <div>
                <h2 class="text-lg font-bold">{{ __('messages.admin_accounts') }}</h2>
                <p class="text-sm text-slate-500">{{ __('messages.list') }}</p>
            </div>
            <a href="{{ route('users.admins.create') }}" class="btn btn-primary">{{ __('messages.new') }}</a>
        </div>
    </x-slot>

    <div class="table-wrap">
        <table class="table-base">
            <thead>
            <tr>
                <th>{{ __('messages.name') }}</th>
                <th>{{ __('messages.email') }}</th>
                <th>{{ __('messages.admin_assignment') }}</th>
                <th>{{ __('messages.actions') }}</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($admins as $admin)
                <tr>
                    <td>{{ $admin->display_name }}</td>
                    <td>{{ $admin->email }}</td>
                    <td>{{ $admin->assignedPastis->pluck('name')->implode(', ') ?: '-' }}</td>
                    <td class="space-x-2">
                        <a href="{{ route('users.admins.edit', $admin) }}" class="btn btn-outline">{{ __('messages.edit') }}</a>
                        <form class="inline" method="POST" action="{{ route('users.admins.destroy', $admin) }}">
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

    <div class="mt-4">{{ $admins->links() }}</div>
</x-app-layout>
