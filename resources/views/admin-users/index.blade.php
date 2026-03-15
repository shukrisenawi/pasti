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
                <th>{{ __('messages.tarikh_lahir') }}</th>
                <th>{{ __('messages.admin_assignment') }}</th>
                <th>{{ __('messages.actions') }}</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($admins as $admin)
                <tr>
                    <td>{{ $admin->display_name }}</td>
                    <td>{{ $admin->email }}</td>
                    <td>{{ $admin->tarikh_lahir?->format('d/m/Y') ?: '-' }}</td>
                    <td>
                        @if($pastiCount > 0 && $admin->assignedPastis->count() === $pastiCount)
                            <span class="badge badge-primary">{{ __('messages.all_pasti') }}</span>
                        @else
                            {{ $admin->assignedPastis->pluck('name')->implode(', ') ?: '-' }}
                        @endif
                    </td>
                    <td class="flex items-center gap-1">
                        <a href="{{ route('users.admins.edit', $admin) }}" class="btn btn-ghost btn-xs btn-circle text-amber-600" title="{{ __('messages.edit') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                            </svg>
                        </a>
                        <form class="inline m-0" method="POST" action="{{ route('users.admins.destroy', $admin) }}">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-ghost btn-xs btn-circle text-rose-600" onclick="return confirm('Delete?')" title="{{ __('messages.delete') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                </svg>
                            </button>
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
