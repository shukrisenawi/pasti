<?php

namespace App\Http\Controllers;

use App\Models\Program;
use App\Models\ProgramParticipation;
use App\Models\ProgramStatus;
use App\Models\User;
use App\Notifications\ProgramAbsenceReasonSubmittedNotification;
use App\Services\KpiCalculationService;
use App\Services\ProgramParticipationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class ProgramParticipationController extends Controller
{
    public function __construct(
        private readonly ProgramParticipationService $participationService,
        private readonly KpiCalculationService $kpiCalculationService,
    ) {
    }

    public function updateStatus(Request $request, Program $program, int $guruId): RedirectResponse
    {
        $user = $request->user();
        if ($this->isGuruOnly($user)) {
            $currentGuruId = $user->guru?->id;
            abort_unless($currentGuruId && $currentGuruId === $guruId, 403);
            abort_unless($program->gurus()->where('gurus.id', $currentGuruId)->exists(), 403);
        }

        $data = $request->validate([
            'program_status_id' => ['nullable', 'integer', 'exists:program_statuses,id'],
            'absence_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $selectedStatus = null;
        if (isset($data['program_status_id'])) {
            $selectedStatus = ProgramStatus::query()->findOrFail($data['program_status_id']);
            abort_unless(in_array($selectedStatus->code, ['HADIR', 'TIDAK_HADIR'], true), 422);
        }

        if (
            $program->require_absence_reason
            && $selectedStatus?->code === 'TIDAK_HADIR'
            && blank($data['absence_reason'] ?? null)
        ) {
            return back()->withErrors([
                'absence_reason' => __('messages.absence_reason_required'),
            ])->withInput();
        }

        $existingParticipation = ProgramParticipation::query()
            ->where('program_id', $program->id)
            ->where('guru_id', $guruId)
            ->first();

        $newAbsenceReason = $selectedStatus?->code === 'TIDAK_HADIR' ? ($data['absence_reason'] ?? null) : null;

        $participation = $this->participationService->updateStatus(
            $program->id,
            $guruId,
            $data['program_status_id'] ?? null,
            $newAbsenceReason,
            $user->id
        );

        $shouldNotifyAbsenceReason = $user->hasRole('guru')
            && $selectedStatus?->code === 'TIDAK_HADIR'
            && filled($newAbsenceReason)
            && (
                ! $existingParticipation
                || $existingParticipation->program_status_id !== ($data['program_status_id'] ?? null)
                || $existingParticipation->absence_reason !== $newAbsenceReason
            );

        if ($shouldNotifyAbsenceReason) {
            $participation->loadMissing(['guru.user', 'program.pasti']);

            $masterAdmins = User::role('master_admin')->get();
            $relatedAdminsQuery = User::role('admin');

            if ($program->pasti_id) {
                $relatedAdminsQuery->whereHas('assignedPastis', fn ($q) => $q->whereKey($program->pasti_id));
            }

            $recipients = $masterAdmins
                ->merge($relatedAdminsQuery->get())
                ->unique('id')
                ->values();

            if ($recipients->isNotEmpty()) {
                Notification::send($recipients, new ProgramAbsenceReasonSubmittedNotification($participation));
            }
        }

        $this->kpiCalculationService->recalculateForGuru($participation->guru);

        return back()->with('status', __('messages.saved'));
    }

    private function isGuruOnly($user): bool
    {
        return $user->hasRole('guru') && ! $user->hasAnyRole(['master_admin', 'admin']);
    }
}
