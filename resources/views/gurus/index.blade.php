<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold">{{ __('messages.guru') }}</h2>
            <a href="{{ route('users.gurus.create') }}" class="btn btn-primary">{{ __('messages.new') }}</a>
        </div>
    </x-slot>

    <div class="mb-4 flex flex-wrap items-center gap-2">
        <a href="{{ route('users.gurus.index', ['tab' => 'guru']) }}" class="btn {{ $activeTab === 'guru' ? 'btn-primary' : 'btn-outline' }}">
            {{ __('messages.main_teacher') }} ({{ $guruCount }})
        </a>
        <a href="{{ route('users.gurus.index', ['tab' => 'assistant']) }}" class="btn {{ $activeTab === 'assistant' ? 'btn-primary' : 'btn-outline' }}">
            {{ __('messages.assistant_teacher') }} ({{ $assistantCount }})
        </a>
    </div>

    <div class="table-wrap">
        <table class="table-base">
            <thead>
            <tr>
                <th>{{ __('messages.name') }}</th>
                <th>{{ __('messages.pasti') }}</th>
                <th>{{ __('messages.phone') }}</th>
                <th>{{ __('messages.status') }}</th>
                <th>{{ __('messages.kpi_score') }}</th>
                <th>{{ __('messages.actions') }}</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($gurus as $guru)
                <tr>
                    <td>
                        <div class="flex items-center gap-3">
                            <x-avatar :guru="$guru" />
                            <span>{{ $guru->display_name }}</span>
                        </div>
                    </td>
                    <td>{{ $guru->pasti?->name ?? '-' }}</td>
                    <td>{{ $guru->phone ?? '-' }}</td>
                    <td>{{ $guru->active ? __('messages.active') : __('messages.inactive') }}</td>

                    <td>{{ number_format((float) ($guru->kpiSnapshot?->score ?? 0), 2) }}%</td>
                    <td class="space-x-2">
                        <a href="{{ route('users.gurus.edit', $guru) }}" class="btn btn-outline">{{ __('messages.edit') }}</a>
                        <a href="{{ route('kpi.guru.show', $guru) }}" class="btn btn-outline">{{ __('messages.view') }}</a>
                        <form method="POST" action="{{ route('users.gurus.destroy', $guru) }}" class="inline">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-error" onclick="return confirm('Delete?')">{{ __('messages.delete') }}</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center">-</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $gurus->links() }}</div>
</x-app-layout>
