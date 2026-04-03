<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold">{{ __('messages.kpi_guru') }}</h2>
                <p class="text-sm text-slate-500">{{ __('messages.current_year') }}: {{ $currentYear }}</p>
            </div>
            <form method="GET" action="{{ route('kpi.gurus.index') }}" class="flex w-full max-w-md items-center gap-2">
                <input
                    type="text"
                    name="search"
                    value="{{ $search }}"
                    placeholder="{{ __('messages.search') }}..."
                    class="input-base"
                >
                <button class="btn btn-primary" type="submit">{{ __('messages.search') }}</button>
                @if($search !== '')
                    <a href="{{ route('kpi.gurus.index') }}" class="btn btn-outline">{{ __('messages.cancel') }}</a>
                @endif
            </form>
        </div>
    </x-slot>

    <div class="table-wrap">
        <table class="table-base">
            <thead><tr><th>{{ __('messages.name') }}</th><th>{{ __('messages.kpi_score') }}</th><th>{{ __('messages.total_leave_taken') }}</th><th>{{ __('messages.actions') }}</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($gurus as $guru)
                <tr>
                    <td class="flex items-center gap-3">
                        <x-avatar :guru="$guru" size="h-10 w-10" rounded="rounded-2xl" />
                        <span>{{ $guru->display_name }}</span>
                    </td>
                    <td>{{ number_format((float) ($guru->kpiSnapshot?->score ?? 0), 0) }}</td>
                    <td>{{ $guru->leave_notices_current_year_count ?? 0 }}</td>
                    <td>
                        <a href="{{ route('kpi.guru.show', $guru) }}" class="btn btn-ghost btn-xs btn-circle text-primary" title="{{ __('messages.view') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.644C3.399 8.049 7.306 5 12 5c4.694 0 8.601 3.049 9.964 6.678a1.012 1.012 0 010 .644C20.601 15.951 16.694 19 12 19c-4.694 0-8.601-3.049-9.964-6.678z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center">-</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $gurus->links() }}</div>
</x-app-layout>
