<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold">{{ __('messages.laporan_pasti') }}</h2>
                <p class="text-sm text-slate-500">Laporan maklumat elaun guru terbaru mengikut PASTI.</p>
            </div>
        </div>
    </x-slot>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm uppercase">
                <thead class="bg-slate-50 text-xs font-black tracking-[0.16em] text-slate-600">
                    <tr>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-left">Nama Guru</th>
                        <th class="px-4 py-3 text-left">No Kad Pengenalan</th>
                        <th class="px-4 py-3 text-left">No HP</th>
                        <th class="px-4 py-3 text-left">Elaun Transit</th>
                        <th class="px-4 py-3 text-left">Elaun Lain</th>
                        <th class="px-4 py-3 text-left">Nama PASTI</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white text-slate-700">
                    @forelse($reports as $report)
                        @php($latestSalary = $report->latestCompletedSalaryRequest)
                        <tr class="align-top">
                            <td class="px-4 py-3 font-bold {{ $report->active ? 'text-emerald-700' : 'text-rose-700' }}">
                                {{ $report->active ? 'GURU' : 'BERHENTI' }}
                            </td>
                            <td class="px-4 py-3 font-semibold">{{ mb_strtoupper((string) ($report->name ?: '-')) }}</td>
                            <td class="px-4 py-3">{{ mb_strtoupper((string) ($report->kad_pengenalan ?: '-')) }}</td>
                            <td class="px-4 py-3">{{ mb_strtoupper((string) ($report->phone ?: '-')) }}</td>
                            <td class="px-4 py-3">{{ filled($latestSalary?->elaun) ? 'RM ' . number_format((float) $latestSalary->elaun, 2) : '-' }}</td>
                            <td class="px-4 py-3">{{ filled($latestSalary?->elaun_lain) ? 'RM ' . number_format((float) $latestSalary->elaun_lain, 2) : '-' }}</td>
                            <td class="px-4 py-3">{{ mb_strtoupper((string) ($report->pasti?->name ?: '-')) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-slate-500">-</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $reports->links() }}</div>
</x-app-layout>
