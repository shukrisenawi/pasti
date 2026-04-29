<?php

namespace App\Http\Controllers;

use App\Models\Program;
use App\Models\ProgramParticipation;
use App\Models\ProgramStatus;
use App\Models\User;
use App\Notifications\ProgramAbsenceReasonSubmittedNotification;
use App\Services\KpiCalculationService;
use App\Services\N8nWebhookService;
use App\Services\ProgramParticipationService;
use App\Models\Guru;
use Illuminate\Support\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class ProgramParticipationController extends Controller
{
    public function __construct(
        private readonly ProgramParticipationService $participationService,
        private readonly KpiCalculationService $kpiCalculationService,
        private readonly N8nWebhookService $n8nWebhookService,
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
        $realProgramCompletedBefore = $this->allRealProgramResponsesCompleted($program);

        $newAbsenceReason = $selectedStatus?->code === 'TIDAK_HADIR' ? ($data['absence_reason'] ?? null) : null;
        $newAbsenceReasonStatus = $selectedStatus?->code === 'TIDAK_HADIR' && filled($newAbsenceReason)
            ? ProgramParticipationService::ABSENCE_REASON_PENDING
            : null;

        $participation = $this->participationService->updateStatus(
            $program->id,
            $guruId,
            $data['program_status_id'] ?? null,
            $newAbsenceReason,
            $user->id,
            $newAbsenceReasonStatus,
            null,
            null
        );

        $shouldNotifyAbsenceReason = $user->isOperatingAsGuru()
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

        $realProgramCompleted = $this->allRealProgramResponsesCompleted($program);

        if (! $realProgramCompletedBefore && $realProgramCompleted) {
            $this->n8nWebhookService->sendByTemplate(
                N8nWebhookService::KEY_TEXT_ALL_GURU_COMPLETED_THANKS,
                [
                    'perkara' => 'status program',
                ],
                $this->n8nWebhookService->toActionUrl(route('programs.show', $program))
            );
        }

        return back()
            ->with('status', __('messages.saved'))
            ->with('program_status_success_message', 'Dah berjaya update')
            ->with('program_status_success_actor', $user->isOperatingAsAdmin() ? 'admin' : 'guru')
            ->with('program_status_updated_guru_id', $guruId);
    }

    public function reviewAbsenceReason(Request $request, Program $program, int $guruId): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isOperatingAsAdmin(), 403);

        $data = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
        ]);

        $participation = ProgramParticipation::query()
            ->where('program_id', $program->id)
            ->where('guru_id', $guruId)
            ->firstOrFail();
        $realProgramCompletedBefore = $this->allRealProgramResponsesCompleted($program);

        $absentStatusIds = ProgramStatus::query()
            ->where('code', 'TIDAK_HADIR')
            ->pluck('id')
            ->all();

        abort_unless(
            in_array((int) $participation->program_status_id, $absentStatusIds, true)
            && filled($participation->absence_reason),
            422
        );

        $decision = $data['decision'] === 'approved'
            ? ProgramParticipationService::ABSENCE_REASON_APPROVED
            : ProgramParticipationService::ABSENCE_REASON_REJECTED;

        $participation->update([
            'absence_reason_status' => $decision,
            'absence_reason_reviewed_by' => $user->id,
            'absence_reason_reviewed_at' => now(),
            'updated_by' => $user->id,
        ]);

        $participation->loadMissing('guru');
        $this->kpiCalculationService->recalculateForGuru($participation->guru);

        $realProgramCompleted = $this->allRealProgramResponsesCompleted($program);

        if (! $realProgramCompletedBefore && $realProgramCompleted) {
            $this->n8nWebhookService->sendByTemplate(
                N8nWebhookService::KEY_TEXT_ALL_GURU_COMPLETED_THANKS,
                [
                    'perkara' => 'status program',
                ],
                $this->n8nWebhookService->toActionUrl(route('programs.show', $program))
            );
        }

        return redirect()
            ->route('programs.show', $program)
            ->with('status', __('messages.saved'))
            ->with('program_status_success_message', 'Dah berjaya update')
            ->with('program_status_success_actor', 'admin')
            ->with('program_status_updated_guru_id', $guruId);
    }

    private function isGuruOnly($user): bool
    {
        return $user->isOperatingAsGuru();
    }

    private function isTestReminderAccount(Guru $guru): bool
    {
        $displayName = trim(mb_strtolower((string) $guru->display_name));
        $guruName = trim(mb_strtolower((string) $guru->name));

        return in_array('test', [$displayName, $guruName], true);
    }

    private function allRealProgramResponsesCompleted(Program $program): bool
    {
        $participations = $this->realProgramParticipations($program);

        return $participations->isNotEmpty()
            && $participations->every(function (ProgramParticipation $participation): bool {
                return filled($participation->program_status_id)
                    && $participation->absence_reason_status !== ProgramParticipationService::ABSENCE_REASON_PENDING;
            });
    }

    /**
     * @return Collection<int, ProgramParticipation>
     */
    private function realProgramParticipations(Program $program): Collection
    {
        return ProgramParticipation::query()
            ->with('guru.user')
            ->where('program_id', $program->id)
            ->get()
            ->filter(fn (ProgramParticipation $participation): bool => $participation->guru && ! $this->isTestReminderAccount($participation->guru))
            ->unique('guru_id')
            ->values();
    }
}
