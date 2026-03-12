<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-lg font-bold">{{ __('messages.kpi_guru') }}</h2>
            <p class="text-sm text-slate-500">{{ __('messages.current_year') }}: {{ $currentYear }}</p>
        </div>
    </x-slot>

    <div class="table-wrap">
        <table class="table-base">
            <thead><tr><th>{{ __('messages.name') }}</th><th>{{ __('messages.pasti') }}</th><th>{{ __('messages.kpi_score') }}</th><th>{{ __('messages.total_leave_taken') }}</th><th>{{ __('messages.actions') }}</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($gurus as $guru)
                <tr>
                    <td>{{ $guru->display_name }}</td>
                    <td>{{ $guru->pasti?->name ?? '-' }}</td>
                    <td>{{ number_format((float) ($guru->kpiSnapshot?->score ?? 0), 2) }}%</td>
                    <td>{{ $guru->leave_notices_current_year_count ?? 0 }}</td>
                    <td><a href="{{ route('kpi.guru.show', $guru) }}" class="btn btn-outline">{{ __('messages.view') }}</a></td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center">-</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $gurus->links() }}</div>
</x-app-layout>
