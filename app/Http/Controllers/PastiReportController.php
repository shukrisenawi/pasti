<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\GuruSalaryRequest;
use App\Models\Pasti;
use App\Models\PastiInformationRequest;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class PastiReportController extends Controller
{
    public function index(): View
    {
        /** @var User $user */
        $user = auth()->user();
        abort_unless($user->isOperatingAsAdmin(), 403);

        $activeTab = request()->query('tab', 'maklumat-pasti');
        if (! in_array($activeTab, ['maklumat-pasti', 'elaun-guru'], true)) {
            $activeTab = 'maklumat-pasti';
        }

        $reports = Guru::query()
            ->whereRaw('lower(coalesce(gurus.name, \'\')) <> ?', ['test'])
            ->with([
                'pasti',
                'latestSalaryRequest',
                'latestCompletedSalaryRequest',
                'salaryRequests' => fn ($query) => $query
                    ->orderByDesc('completed_at')
                    ->orderByDesc('id'),
            ])
            ->when(
                $user->hasRole('admin') && ! $user->hasRole('master_admin'),
                fn (Builder $query) => $query->whereIn('pasti_id', $user->assignedPastis()->pluck('pastis.id'))
            )
            ->leftJoin('pastis', 'pastis.id', '=', 'gurus.pasti_id')
            ->select('gurus.*')
            ->orderBy('gurus.is_assistant')
            ->orderByRaw("CASE WHEN pastis.name IS NULL OR pastis.name = '' THEN 1 ELSE 0 END")
            ->orderBy('pastis.name')
            ->orderBy('gurus.name')
            ->paginate(20)
            ->through(fn (Guru $guru): Guru => $this->decorateGuruReport($guru))
            ->withQueryString();

        $pastiReports = Pasti::query()
            ->with([
                'latestInformationRequest',
                'latestCompletedInformationRequest',
                'informationRequests' => fn ($query) => $query
                    ->orderByDesc('completed_at')
                    ->orderByDesc('id'),
            ])
            ->when(
                $user->hasRole('admin') && ! $user->hasRole('master_admin'),
                fn (Builder $query) => $query->whereIn('id', $user->assignedPastis()->pluck('pastis.id'))
            )
            ->orderBy('name')
            ->paginate(20, ['*'], 'pastiPage')
            ->through(fn (Pasti $pasti): Pasti => $this->decoratePastiReport($pasti))
            ->withQueryString();

        return view('pasti-reports.index', [
            'reports' => $reports,
            'pastiReports' => $pastiReports,
            'activeTab' => $activeTab,
        ]);
    }

    private function decoratePastiReport(Pasti $pasti): Pasti
    {
        $latestRequest = $pasti->latestInformationRequest;
        $latestCompleted = $pasti->latestCompletedInformationRequest;
        $completedRequests = $pasti->informationRequests
            ->filter(fn (PastiInformationRequest $request): bool => $request->completed_at !== null)
            ->values();
        $previousCompleted = $completedRequests->skip(1)->first();
        $isPending = ! $latestRequest || $latestRequest->completed_at === null;

        $fieldNames = [
            'jumlah_guru',
            'jumlah_pembantu_guru',
            'murid_lelaki_4_tahun',
            'murid_perempuan_4_tahun',
            'murid_lelaki_5_tahun',
            'murid_perempuan_5_tahun',
            'murid_lelaki_6_tahun',
            'murid_perempuan_6_tahun',
        ];

        $fieldStates = collect($fieldNames)
            ->mapWithKeys(fn (string $field): array => [
                $field => $this->reportFieldState($latestCompleted, $previousCompleted, $field, $isPending),
            ])
            ->all();

        $latestTotal = $this->maklumatPastiJumlah($latestCompleted);
        $previousTotal = $this->maklumatPastiJumlah($previousCompleted);

        $pasti->maklumat_pasti_jumlah = $latestTotal;
        $pasti->report_field_states = $fieldStates;
        $pasti->report_total_state = $isPending
            ? 'pending'
            : ($previousCompleted && $latestTotal !== $previousTotal ? 'changed' : 'unchanged');

        return $pasti;
    }

    private function reportFieldState(
        ?PastiInformationRequest $latestCompleted,
        ?PastiInformationRequest $previousCompleted,
        string $field,
        bool $isPending
    ): string {
        if ($isPending) {
            return 'pending';
        }

        if (! $latestCompleted || ! $previousCompleted) {
            return 'unchanged';
        }

        return (int) ($latestCompleted->{$field} ?? 0) !== (int) ($previousCompleted->{$field} ?? 0)
            ? 'changed'
            : 'unchanged';
    }

    private function maklumatPastiJumlah(?PastiInformationRequest $request): int
    {
        if (! $request) {
            return 0;
        }

        return (int) (
            ($request->murid_lelaki_4_tahun ?? 0)
            + ($request->murid_perempuan_4_tahun ?? 0)
            + ($request->murid_lelaki_5_tahun ?? 0)
            + ($request->murid_perempuan_5_tahun ?? 0)
            + ($request->murid_lelaki_6_tahun ?? 0)
            + ($request->murid_perempuan_6_tahun ?? 0)
        );
    }

    private function decorateGuruReport(Guru $guru): Guru
    {
        $latestRequest = $guru->latestSalaryRequest;
        $latestCompleted = $guru->latestCompletedSalaryRequest;
        $completedRequests = $guru->salaryRequests
            ->filter(fn (GuruSalaryRequest $request): bool => $request->completed_at !== null)
            ->values();
        $previousCompleted = $completedRequests->skip(1)->first();
        $isPending = ! $guru->is_assistant && (! $latestRequest || $latestRequest->completed_at === null);

        $currentValues = [
            'gaji' => $guru->is_assistant ? $guru->elaun : $latestCompleted?->gaji,
            'elaun_transit' => $guru->is_assistant ? $guru->elaun_transit : ($latestCompleted?->elaun_transit ?? $latestCompleted?->elaun),
            'elaun_lain' => $guru->is_assistant ? $guru->elaun_lain : $latestCompleted?->elaun_lain,
        ];

        $previousValues = [
            'gaji' => $previousCompleted?->gaji,
            'elaun_transit' => $previousCompleted?->elaun_transit ?? $previousCompleted?->elaun,
            'elaun_lain' => $previousCompleted?->elaun_lain,
        ];

        $guru->salary_report_states = collect(array_keys($currentValues))
            ->mapWithKeys(fn (string $field): array => [
                $field => $this->salaryFieldState(
                    $currentValues[$field] ?? null,
                    $previousValues[$field] ?? null,
                    $isPending
                ),
            ])
            ->all();

        return $guru;
    }

    private function salaryFieldState(mixed $currentValue, mixed $previousValue, bool $isPending): string
    {
        if ($isPending) {
            return 'pending';
        }

        if ($previousValue === null || $currentValue === null) {
            return 'unchanged';
        }

        return (float) $currentValue !== (float) $previousValue
            ? 'changed'
            : 'unchanged';
    }
}
