<?php

namespace App\Services;

use App\Models\ProgramParticipation;

class ProgramParticipationService
{
    public const ABSENCE_REASON_PENDING = 'pending';
    public const ABSENCE_REASON_APPROVED = 'approved';
    public const ABSENCE_REASON_REJECTED = 'rejected';

    public function syncTeachers(int $programId, array $guruIds, int $updatedBy): void
    {
        $existingGuruIds = ProgramParticipation::query()
            ->where('program_id', $programId)
            ->pluck('guru_id')
            ->all();

        $toDelete = array_diff($existingGuruIds, $guruIds);

        if ($toDelete !== []) {
            ProgramParticipation::query()
                ->where('program_id', $programId)
                ->whereIn('guru_id', $toDelete)
                ->delete();
        }

        foreach ($guruIds as $guruId) {
            ProgramParticipation::query()->updateOrCreate(
                ['program_id' => $programId, 'guru_id' => $guruId],
                ['updated_by' => $updatedBy]
            );
        }
    }

    public function updateStatus(
        int $programId,
        int $guruId,
        ?int $programStatusId,
        ?string $absenceReason,
        int $updatedBy,
        ?string $absenceReasonStatus = null,
        ?int $absenceReasonReviewedBy = null,
        $absenceReasonReviewedAt = null
    ): ProgramParticipation
    {
        return ProgramParticipation::query()->updateOrCreate(
            ['program_id' => $programId, 'guru_id' => $guruId],
            [
                'program_status_id' => $programStatusId,
                'absence_reason' => $absenceReason,
                'absence_reason_status' => $absenceReasonStatus,
                'absence_reason_reviewed_by' => $absenceReasonReviewedBy,
                'absence_reason_reviewed_at' => $absenceReasonReviewedAt,
                'updated_by' => $updatedBy,
            ]
        );
    }
}
