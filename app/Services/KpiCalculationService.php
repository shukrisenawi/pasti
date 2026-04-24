<?php

namespace App\Services;

use App\Models\Guru;
use App\Models\KpiSnapshot;
use App\Models\ProgramStatus;
use App\Services\ProgramParticipationService;

class KpiCalculationService
{
    public function recalculateForGuru(Guru $guru): KpiSnapshot
    {
        $currentYear = (int) now()->year;

        $baseQuery = $guru->programs()
            ->whereYear('programs.program_date', $currentYear);

        $totalInvited = (int) (clone $baseQuery)->sum('programs.markah');

        $hadirStatusIds = ProgramStatus::query()
            ->where('is_hadir', true)
            ->pluck('id');

        $absentStatusIds = ProgramStatus::query()
            ->where('code', 'TIDAK_HADIR')
            ->pluck('id');

        $totalHadir = (int) (clone $baseQuery)
            ->where(function ($query) use ($hadirStatusIds, $absentStatusIds): void {
                if ($hadirStatusIds->isNotEmpty()) {
                    $query->whereIn('program_teacher.program_status_id', $hadirStatusIds);
                }

                if ($absentStatusIds->isNotEmpty()) {
                    $method = $hadirStatusIds->isNotEmpty() ? 'orWhere' : 'where';

                    $query->{$method}(function ($approvedAbsenceQuery) use ($absentStatusIds): void {
                        $approvedAbsenceQuery
                            ->whereIn('program_teacher.program_status_id', $absentStatusIds)
                            ->where('program_teacher.absence_reason_status', ProgramParticipationService::ABSENCE_REASON_APPROVED);
                    });
                }
            })
            ->sum('programs.markah');

        $score = $totalHadir;

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
