<?php

namespace App\Http\Controllers;

use App\Models\AdminMessage;
use App\Models\Guru;
use App\Models\Program;
use App\Models\ProgramStatus;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user()->load('ajkPositions');
        $currentYear = (int) now()->year;
        $programQuery = Program::query();
        $topKpiGurus = collect();
        $latestInboxMessage = null;

        if ($this->isGuruOnly($user)) {
            $guruId = $user->guru?->id;
            $programQuery->whereHas('gurus', fn ($q) => $q->where('gurus.id', $guruId));

            // Fetch pending PASTI information requests for this guru
            if ($guruId) {
                $pendingPastiInfoCount = \App\Models\PastiInformationRequest::query()
                    ->whereHas('pasti', fn ($q) => $q->whereHas('gurus', fn ($vg) => $vg->where('gurus.id', $guruId)))
                    ->whereNull('completed_at')
                    ->count();
            }
        }

        if ($user->hasAnyRole(['master_admin', 'admin'])) {
            $topGuruQuery = Guru::query()
                ->with(['user', 'pasti', 'kpiSnapshot'])
                ->withLeaveDaysForYear($currentYear)
                ->whereHas('kpiSnapshot');

            if ($user->hasRole('admin') && ! $user->hasRole('master_admin')) {
                $topGuruQuery->whereIn('pasti_id', $this->assignedPastiIds($user));
            }

            $rankedGurus = $topGuruQuery->get();

            if ($rankedGurus->isNotEmpty()) {
                $topScore = (float) $rankedGurus->max(fn (Guru $guru) => (float) ($guru->kpiSnapshot?->score ?? 0));

                $topScoreGurus = $rankedGurus->filter(
                    fn (Guru $guru) => abs((float) ($guru->kpiSnapshot?->score ?? 0) - $topScore) < 0.00001
                );

                $minimumLeaveCount = (int) $topScoreGurus->min('leave_notices_current_year_count');

                $topKpiGurus = $topScoreGurus
                    ->where('leave_notices_current_year_count', $minimumLeaveCount)
                    ->sortBy(fn (Guru $guru) => $guru->display_name)
                    ->values();
            }
        }

        if ($user->hasRole('guru')) {
            $latestInboxMessage = AdminMessage::query()
                ->with(['sender', 'replies'])
                ->withMax('replies', 'created_at')
                ->whereHas('recipientLinks', fn ($q) => $q->where('user_id', $user->id))
                ->orderByRaw('COALESCE(replies_max_created_at, admin_messages.created_at) DESC')
                ->latest('id')
                ->first();
        } elseif ($user->hasRole('master_admin')) {
            $latestInboxMessage = AdminMessage::query()
                ->with(['sender', 'replies'])
                ->withMax('replies', 'created_at')
                ->orderByRaw('COALESCE(replies_max_created_at, admin_messages.created_at) DESC')
                ->latest('id')
                ->first();
        } elseif ($user->hasRole('admin')) {
            $latestInboxMessage = AdminMessage::query()
                ->with(['sender', 'replies'])
                ->withMax('replies', 'created_at')
                ->where('sender_id', $user->id)
                ->orderByRaw('COALESCE(replies_max_created_at, admin_messages.created_at) DESC')
                ->latest('id')
                ->first();
        }

        $latestProgram = (clone $programQuery)
            ->with(['participations.status'])
            ->orderByDesc('program_date')
            ->orderByDesc('created_at')
            ->first();

        $currentGuruId = $user->guru?->id;
        $currentParticipation = null;
        $statuses = collect();

        if ($user->hasRole('guru') && $latestProgram && $currentGuruId) {
            $currentParticipation = $latestProgram->participations->firstWhere('guru_id', $currentGuruId);
            $statuses = ProgramStatus::query()
                ->whereIn('code', ['HADIR', 'TIDAK_HADIR'])
                ->orderBy('is_hadir', 'desc')
                ->get();
        }

        $guruTeachingDuration = '-';
        if ($user->guru?->joined_at) {
            $joinedAt = $user->guru->joined_at->startOfDay();
            $today = now()->startOfDay();
            $months = $joinedAt->diffInMonths($today);
            $years = intdiv($months, 12);
            $remainingMonths = $months % 12;

            $durationParts = [];
            if ($years > 0) {
                $durationParts[] = $years.' tahun';
            }

            if ($remainingMonths > 0 || $durationParts === []) {
                $durationParts[] = $remainingMonths.' bulan';
            }

            $guruTeachingDuration = implode(' ', $durationParts);
        }

        return view('dashboard', [
            'latestProgram' => $latestProgram,
            'currentParticipation' => $currentParticipation,
            'statuses' => $statuses,
            'canUpdateOwnStatus' => $user->hasRole('guru') && (bool) $currentParticipation,
            'topKpiGurus' => $topKpiGurus,
            'latestYear' => $currentYear,
            'latestInboxMessage' => $latestInboxMessage,
            'pendingPastiInfoCount' => $pendingPastiInfoCount ?? 0,
            'guruLeaveDays' => $user->guru ? \App\Models\Guru::where('id', $user->guru->id)->withLeaveDaysForYear($currentYear)->first()?->leave_notices_current_year_count : 0,
            'guruTeachingDuration' => $guruTeachingDuration,
            'userAjkPositions' => $user->ajkPositions->sortBy('name')->values(),
        ]);
    }

    private function isGuruOnly($user): bool
    {
        return $user->hasRole('guru') && ! $user->hasAnyRole(['master_admin', 'admin']);
    }
}
