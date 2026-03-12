<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-lg font-bold">{{ __('messages.kpi_score') }} - {{ $guru->display_name }}</h2>
            <p class="text-sm text-slate-500">{{ __('messages.current_year') }}: {{ $currentYear }}</p>
        </div>
    </x-slot>

    <div class="grid gap-4 md:grid-cols-4">
        <div class="stat-card">
            <p class="stat-title">{{ __('messages.total_invited') }}</p>
            <p class="stat-value">{{ $guru->kpiSnapshot?->total_invited ?? 0 }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-title">{{ __('messages.total_hadir') }}</p>
            <p class="stat-value">{{ $guru->kpiSnapshot?->total_hadir ?? 0 }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-title">{{ __('messages.total_leave_taken') }}</p>
            <p class="stat-value">{{ $guru->leave_notices_current_year_count ?? 0 }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-title">{{ __('messages.kpi_score') }}</p>
            <p class="stat-value">{{ number_format((float) ($guru->kpiSnapshot?->score ?? 0), 2) }}%</p>
        </div>
    </div>

    <div class="table-wrap mt-4">
        <table class="table-base">
            <thead><tr><th>{{ __('messages.title') }}</th><th>{{ __('messages.date') }}</th><th>{{ __('messages.location') }}</th><th>{{ __('messages.status') }}</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($guru->programs as $program)
                <tr>
                    <td>{{ $program->title }}</td>
                    <td>{{ $program->program_date?->format('d/m/Y') }}</td>
                    <td>{{ $program->location ?? '-' }}</td>
                    <td>{{ $statusNames[$program->pivot->program_status_id] ?? '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center">-</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</x-app-layout>