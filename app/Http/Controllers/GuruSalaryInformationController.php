<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\GuruSalaryRequest;
use App\Models\User;
use App\Notifications\GuruSalaryRequestedNotification;
use App\Notifications\GuruSalaryUpdatedNotification;
use App\Services\N8nWebhookService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class GuruSalaryInformationController extends Controller
{
    public function __construct(
        private readonly N8nWebhookService $n8nWebhookService,
    ) {
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->hasAnyRole(['master_admin', 'admin', 'guru']), 403);

        $search = trim((string) $request->query('search', ''));
        $accessibleGurusQuery = $this->accessibleGurusQueryForUser($user);
        $allAccessibleGuruIds = (clone $accessibleGurusQuery)->pluck('gurus.id');
        $pendingReminderGurus = $this->pendingReminderGurusForAdmin($allAccessibleGuruIds);
        $hasPendingRequests = $pendingReminderGurus->isNotEmpty();

        $gurus = (clone $accessibleGurusQuery)
            ->select('gurus.*')
            ->selectSub(
                GuruSalaryRequest::query()
                    ->selectRaw('MAX(completed_at)')
                    ->whereColumn('guru_salary_requests.guru_id', 'gurus.id'),
                'latest_response_at'
            )
            ->with(['pasti', 'user'])
            ->when(
                $search !== '',
                fn (Builder $query) => $query->where(function (Builder $searchQuery) use ($search): void {
                    $keyword = '%' . $search . '%';
                    $searchQuery
                        ->where('gurus.name', 'like', $keyword)
                        ->orWhereHas('pasti', fn (Builder $pastiQuery) => $pastiQuery->where('name', 'like', $keyword));
                })
            )
            ->leftJoin('pastis', 'pastis.id', '=', 'gurus.pasti_id')
            ->orderByDesc('latest_response_at')
            ->orderByRaw("CASE WHEN pastis.name IS NULL OR pastis.name = '' THEN 1 ELSE 0 END")
            ->orderBy('pastis.name')
            ->orderBy('gurus.name')
            ->paginate(9)
            ->withQueryString();

        $guruIds = collect($gurus->items())->pluck('id')->all();

        $requestGroups = GuruSalaryRequest::query()
            ->with(['requestedBy', 'completedBy'])
            ->whereIn('guru_id', $guruIds)
            ->orderByDesc('id')
            ->get()
            ->groupBy('guru_id');

        return view('guru-salary-information.index', [
            'gurus' => $gurus,
            'latestRequests' => $requestGroups->map(fn ($items) => $items->first()),
            'latestCompletedRequests' => $requestGroups->map(fn ($items) => $items->firstWhere(fn (GuruSalaryRequest $item) => $item->completed_at !== null)),
            'canRequest' => $user->isOperatingAsAdmin(),
            'canRequestAll' => $user->isOperatingAsAdmin() && ! $hasPendingRequests && $allAccessibleGuruIds->isNotEmpty(),
            'canRequestReminder' => $user->isOperatingAsAdmin() && $pendingReminderGurus->isNotEmpty(),
            'hasPendingRequests' => $hasPendingRequests,
            'pendingReminderCount' => $pendingReminderGurus->count(),
            'isGuru' => $user->isOperatingAsGuru(),
            'guruId' => $user->guru?->id,
            'search' => $search,
        ]);
    }

    public function requestAllUpdates(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isOperatingAsAdmin(), 403);

        $gurus = $this->accessibleGurusQueryForUser($user)->get(['gurus.id', 'gurus.user_id']);
        if ($gurus->isEmpty()) {
            return back();
        }

        $pendingReminderGurus = $this->pendingReminderGurusForAdmin($gurus->pluck('id'));

        if ($pendingReminderGurus->isNotEmpty()) {
            return back()->withErrors([
                'guru_salary_information' => __('messages.guru_salary_info_wait_until_complete'),
            ]);
        }

        $now = now();
        foreach ($gurus as $guru) {
            $salaryRequest = GuruSalaryRequest::query()->create([
                'guru_id' => $guru->id,
                'requested_by' => $user->id,
                'requested_at' => $now,
            ]);
            $salaryRequest->setRelation('guru', $guru);

            $recipient = $guru->user_id ? User::query()->find($guru->user_id) : null;
            if ($recipient) {
                Notification::send($recipient, new GuruSalaryRequestedNotification($salaryRequest));
            }
        }

        $this->n8nWebhookService->sendByTemplate(
            N8nWebhookService::KEY_TEXT_SALARY_REQUEST,
            ['tarikh' => now()->format('d/m/Y')],
            $this->n8nWebhookService->toActionUrl(route('guru-salary-information.index'))
        );

        return back()->with('status', __('messages.guru_salary_info_request_sent'));
    }

    public function requestPendingResponses(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isOperatingAsAdmin(), 403);

        $accessibleGurusQuery = $this->accessibleGurusQueryForUser($user);
        $guruIds = (clone $accessibleGurusQuery)->pluck('gurus.id');

        if ($guruIds->isEmpty()) {
            return back()->with('status', 'Tiada guru untuk dihantar.');
        }

        $pendingReminderGurus = $this->pendingReminderGurusForAdmin($guruIds);
        if ($pendingReminderGurus->isEmpty()) {
            return back()->with('status', 'Semua guru sudah hantar respon.');
        }

        $senaraiGuru = $pendingReminderGurus
            ->map(fn (Guru $guru, int $index) => ($index + 1) . '- ' . $guru->display_name)
            ->implode("\n");

        $this->n8nWebhookService->sendByTemplate(
            N8nWebhookService::KEY_TEXT_GURU_SALARY_RESPONSE_REMINDER,
            ['senarai_guru' => $senaraiGuru],
            $this->n8nWebhookService->toActionUrl(route('guru-salary-information.index'))
        );

        return back()->with('status', 'Mesej telah berjaya dihantar ke group guru.');
    }

    public function edit(Request $request, GuruSalaryRequest $guruSalaryRequest): View|RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isOperatingAsGuru(), 403);
        $this->ensureGuruCanFill($user, $guruSalaryRequest);

        if ($guruSalaryRequest->completed_at !== null) {
            return redirect()
                ->route('guru-salary-information.index')
                ->withErrors(['guru_salary_information' => __('messages.guru_salary_info_already_completed')]);
        }

        return view('guru-salary-information.form', [
            'salaryRequest' => $guruSalaryRequest->load('guru.pasti'),
        ]);
    }

    public function update(Request $request, GuruSalaryRequest $guruSalaryRequest): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isOperatingAsGuru(), 403);
        $this->ensureGuruCanFill($user, $guruSalaryRequest);

        $data = $request->validate([
            'gaji' => ['required', 'numeric', 'min:0'],
            'elaun' => ['required', 'numeric', 'min:0'],
        ]);

        $affectedRows = GuruSalaryRequest::query()
            ->whereKey($guruSalaryRequest->id)
            ->whereNull('completed_at')
            ->update([
                ...$data,
                'completed_by' => $user->id,
                'completed_at' => now(),
            ]);

        if ($affectedRows === 0) {
            return redirect()
                ->route('guru-salary-information.index')
                ->withErrors(['guru_salary_information' => __('messages.guru_salary_info_already_completed')]);
        }

        $guruSalaryRequest->loadMissing(['guru.pasti', 'completedBy']);

        $masterAdmins = User::role('master_admin')->get();
        $relatedAdmins = User::role('admin')
            ->whereHas('assignedPastis', fn ($query) => $query->whereKey($guruSalaryRequest->guru?->pasti_id))
            ->get();
        $adminRecipients = $masterAdmins->merge($relatedAdmins)->unique('id')->values();

        if ($adminRecipients->isNotEmpty()) {
            Notification::send($adminRecipients, new GuruSalaryUpdatedNotification($guruSalaryRequest));
        }

        $allGuruSalaryCompleted = ! GuruSalaryRequest::query()
            ->whereNull('completed_at')
            ->exists();

        if ($allGuruSalaryCompleted) {
            $this->n8nWebhookService->sendGroup2ByTemplate(
                N8nWebhookService::KEY_TEXT_ALL_GURU_SALARY_COMPLETED,
                ['tarikh' => now()->format('d/m/Y H:i')],
                $this->n8nWebhookService->toActionUrl(route('guru-salary-information.index'))
            );

            $this->n8nWebhookService->sendByTemplate(
                N8nWebhookService::KEY_TEXT_ALL_GURU_COMPLETED_THANKS,
                [
                    'perkara' => 'maklumat gaji guru',
                ],
                $this->n8nWebhookService->toActionUrl(route('guru-salary-information.index'))
            );
        }

        return redirect()->route('guru-salary-information.index')->with('status', __('messages.saved'));
    }

    private function accessibleGurusQueryForUser(User $user): Builder
    {
        $query = Guru::query()
            ->where('is_assistant', false)
            ->where('active', true)
            ->whereNotNull('user_id');

        if ($user->isOperatingAsGuru()) {
            $query->whereKey($user->guru?->id ?: 0);
        } elseif ($user->hasRole('admin') && ! $user->hasRole('master_admin')) {
            $query->whereIn('pasti_id', $this->assignedPastiIds($user));
        }

        return $query;
    }

    private function ensureGuruCanFill(User $user, GuruSalaryRequest $salaryRequest): void
    {
        $guruId = $user->guru?->id;
        abort_unless($guruId && (int) $guruId === (int) $salaryRequest->guru_id, 403);

        $latestRequestId = GuruSalaryRequest::query()
            ->where('guru_id', $salaryRequest->guru_id)
            ->latest('id')
            ->value('id');

        abort_unless($latestRequestId && (int) $latestRequestId === (int) $salaryRequest->id, 403);
    }

    private function isTestReminderAccount(Guru $guru): bool
    {
        $displayName = trim(mb_strtolower((string) $guru->display_name));
        $guruName = trim(mb_strtolower((string) $guru->name));

        return in_array('test', [$displayName, $guruName], true);
    }

    private function pendingReminderGurusForAdmin(Collection $guruIds): Collection
    {
        if ($guruIds->isEmpty()) {
            return collect();
        }

        return GuruSalaryRequest::query()
            ->with('guru.user')
            ->whereIn('guru_id', $guruIds->all())
            ->whereNull('completed_at')
            ->get()
            ->pluck('guru')
            ->filter()
            ->reject(fn (Guru $guru): bool => $this->isTestReminderAccount($guru))
            ->unique('id')
            ->sortBy(fn (Guru $guru) => $guru->display_name)
            ->values();
    }
}
