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
                <th>{{ __('messages.terima_anugerah') }}</th>
                <th>{{ __('messages.kpi_score') }}</th>
                <th>{{ __('messages.actions') }}</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($gurus as $guru)
                <tr>
                    <td>
                        <div class="flex items-center gap-3">
                            <img src="{{ $guru->avatar_url }}" alt="{{ $guru->display_name }}" class="h-9 w-9 rounded-full border border-base-300 object-cover">
                            <span>{{ $guru->display_name }}</span>
                        </div>
                    </td>
                    <td>{{ $guru->pasti?->name ?? '-' }}</td>
                    <td>{{ $guru->phone ?? '-' }}</td>
                    <td>{{ $guru->active ? __('messages.active') : __('messages.inactive') }}</td>
                    <td>
                        @if($guru->terima_anugerah)
                            <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-800">
                                <svg class="h-3 w-3 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                                {{ __('messages.terima_anugerah') }}
                            </span>
                        @else
                            <span class="text-slate-400">-</span>
                        @endif
                    </td>
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
                <tr><td colspan="6" class="text-center">-</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $gurus->links() }}</div>
</x-app-layout>
