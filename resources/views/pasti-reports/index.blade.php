<x-app-layout>
    @php
        $summaryPastiCount = $pastiReports->total();
        $summaryGuruCount = $reports->total();
        $summaryMuridCount = collect($pastiReports->items())->sum(fn ($item) => (int) ($item->maklumat_pasti_jumlah ?? 0));
        $tabBaseClass = 'inline-flex items-center rounded-2xl px-4 py-2 text-sm font-bold transition-all';
        $stateClasses = [
            'changed' => 'bg-emerald-50 text-emerald-900',
            'pending' => 'bg-rose-50 text-rose-900',
            'unchanged' => 'bg-amber-50 text-amber-900',
        ];
    @endphp

    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="text-[11px] font-black uppercase tracking-[0.24em] text-primary">Laporan Ringkas</p>
                <h2 class="text-xl font-black text-slate-900">{{ __('messages.laporan_pasti') }}</h2>
                <p class="text-sm text-slate-500">Paparan lebih padat untuk semakan maklumat PASTI dan elaun guru terkini.</p>
            </div>
        </div>
    </x-slot>

    <div class="mb-6 grid gap-4 xl:grid-cols-[minmax(0,1fr)_auto]">
        <div class="grid gap-3 sm:grid-cols-3">
            <div class="rounded-2xl border border-emerald-100 bg-gradient-to-br from-emerald-50 to-white px-4 py-3">
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-emerald-700">PASTI Responded</p>
                <p class="mt-1 text-2xl font-black text-slate-900">{{ $summaryPastiCount }}</p>
            </div>
            <div class="rounded-2xl border border-sky-100 bg-gradient-to-br from-sky-50 to-white px-4 py-3">
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-sky-700">Guru Dalam Laporan</p>
                <p class="mt-1 text-2xl font-black text-slate-900">{{ $summaryGuruCount }}</p>
            </div>
            <div class="rounded-2xl border border-amber-100 bg-gradient-to-br from-amber-50 to-white px-4 py-3">
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-amber-700">Jumlah Murid</p>
                <p class="mt-1 text-2xl font-black text-slate-900">{{ $summaryMuridCount }}</p>
            </div>
        </div>

        <div class="flex p-1 bg-slate-100 rounded-2xl w-fit self-start">
        <a href="{{ route('pasti-reports.index', ['tab' => 'maklumat-pasti']) }}"
           class="{{ $tabBaseClass }} {{ $activeTab === 'maklumat-pasti' ? 'bg-white shadow-sm text-primary' : 'text-slate-500 hover:text-slate-700' }}">
            Maklumat PASTI
        </a>
        <a href="{{ route('pasti-reports.index', ['tab' => 'elaun-guru']) }}"
           class="{{ $tabBaseClass }} {{ $activeTab === 'elaun-guru' ? 'bg-white shadow-sm text-primary' : 'text-slate-500 hover:text-slate-700' }}">
            Elaun Guru
        </a>
        </div>
    </div>

    @if($activeTab === 'maklumat-pasti')
        <div class="card overflow-hidden border border-slate-200 bg-white/95 p-0">
            <div class="border-b border-slate-200 px-4 py-3">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-[0.18em] text-primary">Maklumat PASTI</p>
                        <p class="text-sm text-slate-500">Data responded terkini disusun dalam jadual padat untuk semakan cepat.</p>
                    </div>
                    <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.14em] text-slate-600">
                        {{ $pastiReports->total() }} Pasti
                    </span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full border-separate border-spacing-0 text-[13px]">
                    <thead class="sticky top-0 z-10 bg-slate-50 text-[10px] font-black uppercase tracking-[0.18em] text-slate-500">
                        <tr>
                            <th class="sticky left-0 z-20 border-b border-slate-200 bg-slate-50 px-4 py-3 text-left">PASTI</th>
                            <th class="border-b border-slate-200 px-3 py-3 text-center">Guru</th>
                            <th class="border-b border-slate-200 px-3 py-3 text-center">Pembantu</th>
                            <th class="border-b border-slate-200 px-3 py-3 text-center">4 Tahun (L)</th>
                            <th class="border-b border-slate-200 px-3 py-3 text-center">4 Tahun (P)</th>
                            <th class="border-b border-slate-200 px-3 py-3 text-center">5 Tahun (L)</th>
                            <th class="border-b border-slate-200 px-3 py-3 text-center">5 Tahun (P)</th>
                            <th class="border-b border-slate-200 px-3 py-3 text-center">6 Tahun (L)</th>
                            <th class="border-b border-slate-200 px-3 py-3 text-center">6 Tahun (P)</th>
                            <th class="border-b border-slate-200 px-4 py-3 text-center">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white text-slate-700">
                        @forelse($pastiReports as $pastiReport)
                            @php($latestInfo = $pastiReport->latestCompletedInformationRequest)
                            @php($fieldStates = $pastiReport->report_field_states ?? [])
                            <tr
                                data-selectable-row
                                class="align-middle odd:bg-white even:bg-slate-50/55 hover:bg-primary/5 cursor-pointer transition"
                            >
                                <td class="sticky left-0 z-[1] border-b border-slate-100 bg-inherit px-4 py-3">
                                    <div class="min-w-[190px]">
                                        <p class="font-bold uppercase tracking-[0.08em] text-slate-800">{{ $pastiReport->name ?: '-' }}</p>
                                        <p class="text-[11px] text-slate-500">Kemaskini: {{ optional($latestInfo?->completed_at)->format('d/m/Y H:i') ?: '-' }}</p>
                                    </div>
                                </td>
                                <td data-field="jumlah_guru" data-state="{{ $fieldStates['jumlah_guru'] ?? 'unchanged' }}" class="border-b border-slate-100 px-3 py-3 text-center font-semibold {{ $stateClasses[$fieldStates['jumlah_guru'] ?? 'unchanged'] ?? $stateClasses['unchanged'] }}">{{ $latestInfo?->jumlah_guru ?? 0 }}</td>
                                <td data-field="jumlah_pembantu_guru" data-state="{{ $fieldStates['jumlah_pembantu_guru'] ?? 'unchanged' }}" class="border-b border-slate-100 px-3 py-3 text-center font-semibold {{ $stateClasses[$fieldStates['jumlah_pembantu_guru'] ?? 'unchanged'] ?? $stateClasses['unchanged'] }}">{{ $latestInfo?->jumlah_pembantu_guru ?? 0 }}</td>
                                <td data-field="murid_lelaki_4_tahun" data-state="{{ $fieldStates['murid_lelaki_4_tahun'] ?? 'unchanged' }}" class="border-b border-slate-100 px-3 py-3 text-center {{ $stateClasses[$fieldStates['murid_lelaki_4_tahun'] ?? 'unchanged'] ?? $stateClasses['unchanged'] }}">{{ $latestInfo?->murid_lelaki_4_tahun ?? 0 }}</td>
                                <td data-field="murid_perempuan_4_tahun" data-state="{{ $fieldStates['murid_perempuan_4_tahun'] ?? 'unchanged' }}" class="border-b border-slate-100 px-3 py-3 text-center {{ $stateClasses[$fieldStates['murid_perempuan_4_tahun'] ?? 'unchanged'] ?? $stateClasses['unchanged'] }}">{{ $latestInfo?->murid_perempuan_4_tahun ?? 0 }}</td>
                                <td data-field="murid_lelaki_5_tahun" data-state="{{ $fieldStates['murid_lelaki_5_tahun'] ?? 'unchanged' }}" class="border-b border-slate-100 px-3 py-3 text-center {{ $stateClasses[$fieldStates['murid_lelaki_5_tahun'] ?? 'unchanged'] ?? $stateClasses['unchanged'] }}">{{ $latestInfo?->murid_lelaki_5_tahun ?? 0 }}</td>
                                <td data-field="murid_perempuan_5_tahun" data-state="{{ $fieldStates['murid_perempuan_5_tahun'] ?? 'unchanged' }}" class="border-b border-slate-100 px-3 py-3 text-center {{ $stateClasses[$fieldStates['murid_perempuan_5_tahun'] ?? 'unchanged'] ?? $stateClasses['unchanged'] }}">{{ $latestInfo?->murid_perempuan_5_tahun ?? 0 }}</td>
                                <td data-field="murid_lelaki_6_tahun" data-state="{{ $fieldStates['murid_lelaki_6_tahun'] ?? 'unchanged' }}" class="border-b border-slate-100 px-3 py-3 text-center {{ $stateClasses[$fieldStates['murid_lelaki_6_tahun'] ?? 'unchanged'] ?? $stateClasses['unchanged'] }}">{{ $latestInfo?->murid_lelaki_6_tahun ?? 0 }}</td>
                                <td data-field="murid_perempuan_6_tahun" data-state="{{ $fieldStates['murid_perempuan_6_tahun'] ?? 'unchanged' }}" class="border-b border-slate-100 px-3 py-3 text-center {{ $stateClasses[$fieldStates['murid_perempuan_6_tahun'] ?? 'unchanged'] ?? $stateClasses['unchanged'] }}">{{ $latestInfo?->murid_perempuan_6_tahun ?? 0 }}</td>
                                <td class="border-b border-slate-100 px-4 py-3 text-center">
                                    <span data-field="jumlah" data-state="{{ $pastiReport->report_total_state ?? 'unchanged' }}" class="inline-flex min-w-[3.5rem] items-center justify-center rounded-xl px-2.5 py-1 font-black {{ $stateClasses[$pastiReport->report_total_state ?? 'unchanged'] ?? $stateClasses['unchanged'] }}">
                                        {{ $pastiReport->maklumat_pasti_jumlah ?? 0 }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-4 py-10 text-center text-slate-400">-</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-4">{{ $pastiReports->links() }}</div>
    @else
        <div class="card overflow-hidden border border-slate-200 bg-white/95 p-0">
            <div class="border-b border-slate-200 px-4 py-3">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-[0.18em] text-primary">Elaun Guru</p>
                        <p class="text-sm text-slate-500">Susunan padat untuk semakan pantas status guru, PASTI dan nilai elaun.</p>
                    </div>
                    <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.14em] text-slate-600">
                        {{ $reports->total() }} Guru
                    </span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full border-separate border-spacing-0 text-[13px]">
                    <thead class="sticky top-0 z-10 bg-slate-50 text-[10px] font-black uppercase tracking-[0.18em] text-slate-500">
                        <tr>
                            <th class="border-b border-slate-200 px-4 py-3 text-left">Status</th>
                            <th class="border-b border-slate-200 px-4 py-3 text-left">Nama Guru</th>
                            <th class="border-b border-slate-200 px-4 py-3 text-left">No Kad Pengenalan</th>
                            <th class="border-b border-slate-200 px-4 py-3 text-left">No HP</th>
                            <th class="border-b border-slate-200 px-4 py-3 text-right">Elaun</th>
                            <th class="border-b border-slate-200 px-4 py-3 text-right">Elaun Transit</th>
                            <th class="border-b border-slate-200 px-4 py-3 text-right">Elaun Lain</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white text-slate-700">
                        @forelse($reports as $report)
                            @php($latestSalary = $report->latestCompletedSalaryRequest)
                            @php($salaryStates = $report->salary_report_states ?? [])
                            <tr
                                data-selectable-row
                                class="align-middle odd:bg-white even:bg-slate-50/55 hover:bg-primary/5 cursor-pointer transition"
                            >
                                <td class="border-b border-slate-100 px-4 py-3">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-black uppercase tracking-[0.08em] {{ $report->is_assistant ? 'bg-amber-100 text-amber-700' : ($report->active ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700') }}">
                                        {{ $report->is_assistant ? 'PEMBANTU' : ($report->active ? 'Guru' : 'Berhenti') }}
                                    </span>
                                </td>
                                <td class="border-b border-slate-100 px-4 py-3">
                                    <div class="min-w-[180px]">
                                        <p class="font-bold uppercase tracking-[0.08em] text-slate-800">{{ $report->name ?: '-' }}</p>
                                        <p class="text-[11px] text-slate-500">{{ $report->pasti?->name ?: '-' }}</p>
                                    </div>
                                </td>
                                <td class="border-b border-slate-100 px-4 py-3 font-medium">{{ mb_strtoupper((string) ($report->kad_pengenalan ?: '-')) }}</td>
                                <td class="border-b border-slate-100 px-4 py-3 font-medium">{{ mb_strtoupper((string) ($report->phone ?: '-')) }}</td>
                                <td data-field="gaji" data-state="{{ $salaryStates['gaji'] ?? 'unchanged' }}" class="border-b border-slate-100 px-4 py-3 text-right font-black {{ $stateClasses[$salaryStates['gaji'] ?? 'unchanged'] ?? $stateClasses['unchanged'] }}">
                                    {{ filled($report->is_assistant ? $report->elaun : $latestSalary?->gaji) ? 'RM ' . number_format((float) ($report->is_assistant ? $report->elaun : $latestSalary?->gaji), 2) : '-' }}
                                </td>
                                <td data-field="elaun_transit" data-state="{{ $salaryStates['elaun_transit'] ?? 'unchanged' }}" class="border-b border-slate-100 px-4 py-3 text-right font-black {{ $stateClasses[$salaryStates['elaun_transit'] ?? 'unchanged'] ?? $stateClasses['unchanged'] }}">
                                    {{ filled($report->is_assistant ? $report->elaun_transit : ($latestSalary?->elaun_transit ?? $latestSalary?->elaun)) ? 'RM ' . number_format((float) ($report->is_assistant ? $report->elaun_transit : ($latestSalary?->elaun_transit ?? $latestSalary?->elaun)), 2) : '-' }}
                                </td>
                                <td data-field="elaun_lain" data-state="{{ $salaryStates['elaun_lain'] ?? 'unchanged' }}" class="border-b border-slate-100 px-4 py-3 text-right font-black {{ $stateClasses[$salaryStates['elaun_lain'] ?? 'unchanged'] ?? $stateClasses['unchanged'] }}">
                                    {{ filled($report->is_assistant ? $report->elaun_lain : $latestSalary?->elaun_lain) ? 'RM ' . number_format((float) ($report->is_assistant ? $report->elaun_lain : $latestSalary?->elaun_lain), 2) : '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-10 text-center text-slate-400">-</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-4">{{ $reports->links() }}</div>
    @endif
</x-app-layout>
