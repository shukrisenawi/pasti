<?php

namespace App\Services;

use App\Models\Guru;
use App\Models\KpiSnapshot;
use App\Models\ProgramStatus;

class KpiCalculationService
{
    public function recalculateForGuru(Guru $guru): KpiSnapshot
    {
        $currentYear = (int) now()->year;

        $baseQuery = $guru->programs()
            ->whereYear('programs.program_date', $currentYear);

        $totalInvited = (int) (clone $baseQuery)->count();

        $hadirStatusIds = ProgramStatus::query()
            ->where('is_hadir', true)
            ->pluck('id');

        $totalHadir = $hadirStatusIds->isEmpty()
            ? 0
            : (int) (clone $baseQuery)
                ->whereIn('program_teacher.program_status_id', $hadirStatusIds)
                ->count();

        $score = $totalInvited > 0 ? round(($totalHadir / $totalInvited) * 100, 2) : 0;

        return KpiSnapshot::query()->updateOrCreate(
            ['guru_id' => $guru->id],
            [
                'total_invited' => $totalInvited,
                'total_hadir' => $totalHadir,
                'score' => $score,
                'calculated_at' => now(),
            ]
        );
    }

    public function recalculateAll(): void
    {
        Guru::query()->with('programs')->chunkById(100, function ($gurus): void {
            foreach ($gurus as $guru) {
                $this->recalculateForGuru($guru);
            }
        });
    }
}
