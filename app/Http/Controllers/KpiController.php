<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\ProgramStatus;
use App\Services\KpiCalculationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KpiController extends Controller
{
    public function __construct(private readonly KpiCalculationService $kpiCalculationService)
    {
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $currentYear = (int) now()->year;

        if ($user->hasRole('guru')) {
            abort(403);
        }

        $query = Guru::query()
            ->with(['user', 'pasti', 'kpiSnapshot'])
            ->withLeaveDaysForYear($currentYear);

        if ($user->hasRole('admin')) {
            $query->whereIn('pasti_id', $this->assignedPastiIds($user));
        }

        $gurus = $query->paginate(10);

        $gurus->getCollection()->transform(function (Guru $guru) {
            $guru->setRelation('kpiSnapshot', $this->kpiCalculationService->recalculateForGuru($guru));

            return $guru;
        });

        return view('kpi.index', [
            'gurus' => $gurus,
            'currentYear' => $currentYear,
        ]);
    }

    public function show(Request $request, Guru $guru): View
    {
        $user = $request->user();
        $currentYear = (int) now()->year;

        if ($user->hasRole('guru')) {
            abort_unless($user->guru?->id === $guru->id, 403);
        }

        if ($user->hasRole('admin')) {
            abort_unless(in_array((int) $guru->pasti_id, $this->assignedPastiIds($user), true), 403);
        }

        $this->kpiCalculationService->recalculateForGuru($guru);

        $guru->load([
            'user',
            'pasti',
            'kpiSnapshot',
            'programs' => fn ($q) => $q
                ->whereYear('programs.program_date', $currentYear)
                ->orderByDesc('program_date'),
        ]);

        $leaveDays = Guru::query()
            ->whereKey($guru->id)
            ->withLeaveDaysForYear($currentYear)
            ->value('leave_notices_current_year_count');

        $guru->setAttribute('leave_notices_current_year_count', (int) ($leaveDays ?? 0));

        return view('kpi.show', [
            'guru' => $guru,
            'statusNames' => ProgramStatus::query()->pluck('name', 'id'),
            'currentYear' => $currentYear,
        ]);
    }
}
