<?php

namespace App\Http\Controllers;

use App\Models\AdminMessage;
use App\Models\Announcement;
use App\Models\FinancialTransaction;
use App\Models\Guru;
use App\Models\GuruSalaryRequest;
use App\Models\PastiInformationRequest;
use App\Models\Program;
use App\Models\ProgramStatus;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    private const TEST_GURU_EMAIL = 'test@pasti';

    public function __invoke(Request $request)
    {
        $user = $request->user()->load('ajkPositions');
        $currentYear = (int) now()->year;
        $topKpiGurus = collect();
        $latestInboxMessage = null;
        $activeAnnouncements = collect();
        $guruId = null;
        $guruIds = [];
        $isGuruOnly = $this->isGuruOnly($user);
        $adminCashBalance = 0.0;
        $adminBankBalance = 0.0;
        $pendingPastiInfoRequest = null;
        $pendingGuruSalaryRequest = null;
        $birthdayUsers = User::query()
            ->whereNotNull('tarikh_lahir')
            ->whereRaw("DATE_FORMAT(tarikh_lahir, '%m-%d') = ?", [now()->format('m-d')])
            ->when($user->isOperatingAsGuru(), function ($query): void {
                $query->where('email', '<>', self::TEST_GURU_EMAIL);
            })
            ->orderByRaw("CASE WHEN avatar_path IS NULL OR avatar_path = '' THEN 1 ELSE 0 END")
            ->orderBy('name')
            ->get()
            ->unique(fn (User $birthdayUser) => strtolower(trim((string) $birthdayUser->display_name)))
            ->values();

        if ($isGuruOnly) {
            $guruIds = $user->operatingGuruIds();
            $guruId = $guruIds[0] ?? null;

            // Fetch pending PASTI information requests for this guru
            if ($guruId) {
                $pendingPastiInfoRequest = PastiInformationRequest::query()
                    ->whereHas('pasti', fn ($q) => $q->whereHas('gurus', fn ($vg) => $vg->where('gurus.id', $guruId)))
                    ->whereNull('completed_at')
                    ->latest('id')
                    ->first();

                $pendingPastiInfoCount = $pendingPastiInfoRequest ? 1 : 0;

                $pendingGuruSalaryRequest = GuruSalaryRequest::query()
                    ->whereIn('guru_id', $guruIds)
                    ->whereNull('completed_at')
                    ->latest('id')
                    ->first();
            }
        }

        if ($user->isOperatingAsAdmin()) {
            $topGuruQuery = Guru::query()
                ->with(['user', 'pasti', 'kpiSnapshot'])
                ->withLeaveDaysForYear($currentYear)
                ->whereHas('programs');

            if ($user->hasRole('admin') && ! $user->hasRole('master_admin')) {
                $topGuruQuery->whereIn('pasti_id', $this->assignedPastiIds($user));
            }

            $rankedGurus = $topGuruQuery->get();

            if ($rankedGurus->isNotEmpty()) {
                $topScore = (float) $rankedGurus->max(fn (Guru $guru) => (float) ($guru->kpiSnapshot?->score ?? 0));

                $topScoreGurus = $rankedGurus->filter(
                    fn (Guru $guru) => abs((float) ($guru->kpiSnapshot?->score ?? 0) - $topScore) < 0.00001
                );

                $topKpiGurus = $topScoreGurus
                    ->sortBy([
                        fn (Guru $guru) => (int) ($guru->leave_notices_current_year_count ?? 0),
                        fn (Guru $guru) => $guru->display_name,
                    ])
                    ->values();
            }

            $balanceExpression = "SUM(CASE WHEN COALESCE(credit_debit, CASE WHEN transaction_type = 'masuk' THEN 'credit' ELSE 'debit' END) = 'credit' THEN amount ELSE -amount END)";

            $financialBaseQuery = FinancialTransaction::query();
            if ($user->hasRole('admin') && ! $user->hasRole('master_admin')) {
                $assignedPastiIds = $this->assignedPastiIds($user);
                $financialBaseQuery->where(function ($query) use ($assignedPastiIds): void {
                    $query->whereNull('pasti_id');

                    if ($assignedPastiIds !== []) {
                        $query->orWhereIn('pasti_id', $assignedPastiIds);
                    }
                });
            }

            $adminCashBalance = (float) ((clone $financialBaseQuery)
                ->where('payment_method', 'cash')
                ->selectRaw($balanceExpression . ' as balance')
                ->value('balance') ?? 0);

            $adminBankBalance = (float) ((clone $financialBaseQuery)
                ->where(function ($query): void {
                    $query->where('payment_method', 'transfer')
                        ->orWhereNull('payment_method');
                })
                ->selectRaw($balanceExpression . ' as balance')
                ->value('balance') ?? 0);
        }

        if ($user->isOperatingAsGuru()) {
            $latestInboxMessage = AdminMessage::query()
                ->with(['sender', 'replies'])
                ->withMax('replies', 'created_at')
                ->whereHas('recipientLinks', fn ($q) => $q->where('user_id', $user->id))
                ->orderByRaw('COALESCE(replies_max_created_at, admin_messages.created_at) DESC')
                ->latest('id')
                ->first();

            $activeAnnouncements = Announcement::query()
                ->whereDate('expires_at', '>=', now()->toDateString())
                ->whereHas('recipients', fn ($q) => $q->where('users.id', $user->id))
                ->latest('id')
                ->limit(5)
                ->get();
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

        $programsQuery = Program::query()
            ->with(['participations.status'])
            ->when(
                $isGuruOnly,
                fn ($query) => $query->where(function($q) use ($guruIds) {
                    $q->whereHas('gurus', fn ($vg) => $vg->whereIn('gurus.id', $guruIds))
                      ->orWhereNull('pasti_id'); // Show assigned OR global programs
                })->whereDoesntHave('participations', function ($participationQuery) use ($guruIds): void {
                    $participationQuery
                        ->whereIn('guru_id', $guruIds)
                        ->whereNotNull('program_status_id');
                })
            );

        // Get 3 upcoming programs (closest first)
        $latestPrograms = (clone $programsQuery)
            ->whereDate('program_date', '>=', now()->toDateString())
            ->oldest('program_date')
            ->oldest('program_time')
            ->oldest('id')
            ->limit(3)
            ->get();

        // If fewer than 3 upcoming, pad with recent past programs
        if ($latestPrograms->count() < 3) {
            $pastPrograms = (clone $programsQuery)
                ->whereDate('program_date', '<', now()->toDateString())
                ->latest('program_date')
                ->latest('program_time')
                ->latest('id')
                ->limit(3 - $latestPrograms->count())
                ->get();
            
            $latestPrograms = $latestPrograms->concat($pastPrograms);
        }

        $latestProgram = $latestPrograms->first();

        $currentGuruId = $guruIds[0] ?? null;
        $currentParticipation = null;
        $participationByProgramId = collect();
        $statuses = collect();

        if ($user->isOperatingAsGuru() && $guruIds !== []) {
            $participationByProgramId = $latestPrograms
                ->mapWithKeys(function (Program $program) use ($guruIds) {
                    $participation = $program->participations->first(
                        fn ($item) => in_array((int) $item->guru_id, $guruIds, true)
                    );

                    return [$program->id => $participation];
                });

            $currentParticipation = $latestProgram
                ? $participationByProgramId->get($latestProgram->id)
                : null;

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
            'latestPrograms' => $latestPrograms,
            'latestProgram' => $latestProgram,
            'currentGuruId' => $currentGuruId,
            'currentParticipation' => $currentParticipation,
            'participationByProgramId' => $participationByProgramId,
            'statuses' => $statuses,
            'canUpdateOwnStatus' => $user->isOperatingAsGuru() && (bool) $latestProgram && (bool) $currentGuruId,
            'topKpiGurus' => $topKpiGurus,
            'latestYear' => $currentYear,
            'latestInboxMessage' => $latestInboxMessage,
            'pendingPastiInfoCount' => $pendingPastiInfoCount ?? 0,
            'pendingPastiInfoRequest' => $pendingPastiInfoRequest,
            'pendingGuruSalaryRequest' => $pendingGuruSalaryRequest,
            'guruLeaveDays' => $user->guru ? \App\Models\Guru::where('id', $user->guru->id)->withLeaveDaysForYear($currentYear)->first()?->leave_notices_current_year_count : 0,
            'guruTeachingDuration' => $guruTeachingDuration,
            'userAjkPositions' => $user->ajkPositions->sortBy('name')->values(),
            'adminCashBalance' => $adminCashBalance,
            'adminBankBalance' => $adminBankBalance,
            'birthdayUsers' => $birthdayUsers,
            'activeAnnouncements' => $activeAnnouncements,
        ]);
    }

    private function isGuruOnly($user): bool
    {
        return $user->isOperatingAsGuru();
    }
}
