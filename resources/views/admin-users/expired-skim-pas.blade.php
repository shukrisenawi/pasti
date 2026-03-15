<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-2">
            <div>
                <h2 class="text-lg font-bold">{{ __('messages.skim_pas_expired_list') }}</h2>
                <p class="text-sm text-slate-500">{{ __('messages.list') }}</p>
            </div>
        </div>
    </x-slot>

    <div class="table-wrap">
        <table class="table-base">
            <thead>
            <tr>
                <th>{{ __('messages.name') }}</th>
                <th>{{ __('messages.tarikh_exp_skim_pas') }}</th>
                <th>{{ __('messages.actions') }}</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($users as $user)
                <tr>
                    <td>
                        <div class="flex items-center gap-3">
                            <x-avatar :user="$user" size="h-10 w-10" rounded="rounded-xl" />
                            <div>
                                <p class="font-bold">{{ $user->display_name }}</p>
                                <p class="text-xs text-slate-500">
                                    @if($user->hasRole('admin'))
                                        <span class="badge badge-xs">Admin</span>
                                    @endif
                                    @if($user->hasRole('guru'))
                                        <span class="badge badge-xs">Guru</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="font-bold text-red-600">
                            {{ $user->tarikh_exp_skim_pas?->format('d/m/Y') ?: '-' }}
                        </span>
                        <p class="text-[10px] text-slate-400">
                            {{ $user->tarikh_exp_skim_pas?->diffForHumans() }}
                        </p>
                    </td>
                    <td class="space-x-2">
                        @if($user->hasRole('admin'))
                            <a href="{{ route('users.admins.edit', $user) }}" class="btn btn-outline">{{ __('messages.edit') }}</a>
                        @elseif($user->hasRole('guru') && $user->guru)
                            <a href="{{ route('users.gurus.edit', $user->guru) }}" class="btn btn-outline">{{ __('messages.edit') }}</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="3" class="py-10 text-center text-slate-500">{{ __('messages.no_data') ?? 'Tiada rekod' }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
</x-app-layout>
