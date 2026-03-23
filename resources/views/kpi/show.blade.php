<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-lg font-bold">{{ __('messages.kpi_score') }} - {{ $guru->display_name }}</h2>
            <p class="text-sm text-slate-500">{{ __('messages.current_year') }}: {{ $currentYear }}</p>
        </div>
    </x-slot>

    <div class="grid grid-cols-2 gap-4 md:grid-cols-4 mb-6">
        <div class="bg-white rounded-2xl p-5 shadow-card border border-slate-50">
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 leading-none mb-2">{{ __('messages.total_invited') }}</p>
            <div class="flex items-baseline gap-1">
                <p class="text-2xl font-black text-slate-900">{{ $guru->kpiSnapshot?->total_invited ?? 0 }}</p>
                <p class="text-xs font-bold text-slate-400">Jemputan</p>
            </div>
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-card border border-slate-50">
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 leading-none mb-2">{{ __('messages.total_hadir') }}</p>
            <div class="flex items-baseline gap-1">
                <p class="text-2xl font-black text-emerald-600">{{ $guru->kpiSnapshot?->total_hadir ?? 0 }}</p>
                <p class="text-xs font-bold text-slate-400">Hadir</p>
            </div>
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-card border border-slate-50">
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 leading-none mb-2">{{ __('messages.total_leave_taken') }}</p>
            <div class="flex items-baseline gap-1">
                <p class="text-2xl font-black text-orange-600">{{ $guru->leave_notices_current_year_count ?? 0 }}</p>
                <p class="text-xs font-bold text-slate-400">Hari</p>
            </div>
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-card border border-primary/20 bg-primary/5">
            <p class="text-[10px] font-bold uppercase tracking-widest text-primary leading-none mb-2">{{ __('messages.kpi_score') }}</p>
            <div class="flex items-baseline gap-1">
                <p class="text-2xl font-black text-primary">{{ number_format((float) ($guru->kpiSnapshot?->score ?? 0), 2) }}</p>
                <p class="text-xs font-bold text-primary">%</p>
            </div>
        </div>
    </div>

    <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400">{{ __('messages.programs') }}</h3>
    </div>

    {{-- Mobile View --}}
    <div class="grid grid-cols-1 gap-4 md:hidden">
        @forelse($guru->programs as $program)
            <div class="bg-white rounded-2xl shadow-card border border-slate-50 p-4">
                <div class="flex justify-between items-start mb-3">
                    <div class="pr-2">
                        <h4 class="font-bold text-slate-900 leading-tight">{{ $program->title }}</h4>
                        <div class="flex items-center gap-2 mt-1.5 text-xs text-slate-500">
                             <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4v-4m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                             {{ $program->program_date?->format('d/m/Y') }}
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-[10px] font-bold text-slate-400 uppercase leading-none mb-1">Markah</div>
                        <div class="text-lg font-black text-primary">{{ $program->markah }}</div>
                    </div>
                </div>
                
                <div class="flex items-center justify-between border-t border-slate-50 pt-3 mt-3">
                    <div class="flex items-center gap-2 text-xs text-slate-600">
                         <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                         <span class="truncate max-w-[150px]">{{ $program->location ?? '-' }}</span>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider {{ ($statusNames[$program->pivot->program_status_id] ?? '') === 'HADIR' ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-600' }}">
                         {{ $statusNames[$program->pivot->program_status_id] ?? '-' }}
                    </span>
                </div>
            </div>
        @empty
            <div class="text-center py-10 bg-white rounded-2xl border-2 border-dashed border-slate-100 text-slate-400 font-medium">
                {{ __('messages.no_programs_found') }}
            </div>
        @endforelse
    </div>

    {{-- Desktop View --}}
    <div class="table-wrap mt-0 hidden md:block">
        <table class="table-base">
            <thead>
                <tr>
                    <th>{{ __('messages.title') }}</th>
                    <th class="text-center">{{ __('messages.markah') }}</th>
                    <th>{{ __('messages.date') }}</th>
                    <th>{{ __('messages.location') }}</th>
                    <th class="text-right">{{ __('messages.status') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
            @forelse($guru->programs as $program)
                <tr class="hover:bg-slate-50 transition-all">
                    <td class="font-semibold text-slate-700">{{ $program->title }}</td>
                    <td class="text-center">
                        <span class="text-primary font-bold">{{ $program->markah }}</span>
                    </td>
                    <td class="text-slate-500">{{ $program->program_date?->format('d/m/Y') }}</td>
                    <td class="text-slate-500">{{ $program->location ?? '-' }}</td>
                    <td class="text-right">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ ($statusNames[$program->pivot->program_status_id] ?? '') === 'HADIR' ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-800' }}">
                            {{ $statusNames[$program->pivot->program_status_id] ?? '-' }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="py-10 text-center text-slate-400">{{ __('messages.no_programs_found') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</x-app-layout>
