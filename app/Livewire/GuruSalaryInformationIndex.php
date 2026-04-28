<?php

namespace App\Livewire;

use App\Models\Guru;
use App\Models\GuruSalaryRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;

class GuruSalaryInformationIndex extends Component
{
    use WithPagination;

    private const PAGE_NAME = 'guruSalaryPage';

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage(self::PAGE_NAME);
    }

    public function render()
    {
        /** @var User $user */
        $user = auth()->user();
        abort_unless($user->hasAnyRole(['master_admin', 'admin', 'guru']), 403);

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
                trim($this->search) !== '',
                fn (Builder $query) => $query->where(function (Builder $searchQuery): void {
                    $keyword = '%' . trim($this->search) . '%';
                    $searchQuery
                        ->where('gurus.name', 'like', $keyword)
                        ->orWhereHas('pasti', fn (Builder $pastiQuery) => $pastiQuery->where('name', 'like', $keyword));
                })
            )
            ->leftJoin('pastis', 'pastis.id', '=', 'gurus.pasti_id')
            ->orderByDesc('latest_response_at')
            ->orderByDesc('gurus.id')
            ->paginate(9, pageName: self::PAGE_NAME);

        $guruIds = collect($gurus->items())->pluck('id')->all();

        $requestGroups = GuruSalaryRequest::query()
            ->with(['requestedBy', 'completedBy'])
            ->whereIn('guru_id', $guruIds)
            ->orderByDesc('id')
            ->get()
            ->groupBy('guru_id');

        return view('livewire.guru-salary-information-index', [
            'gurus' => $gurus,
            'latestRequests' => $requestGroups->map(fn (Collection $items) => $items->first()),
            'latestCompletedRequests' => $requestGroups->map(fn (Collection $items) => $items->firstWhere(fn (GuruSalaryRequest $item) => $item->completed_at !== null)),
            'canRequest' => $user->isOperatingAsAdmin(),
            'canRequestAll' => $user->isOperatingAsAdmin() && ! $hasPendingRequests && $allAccessibleGuruIds->isNotEmpty(),
            'canRequestReminder' => $user->isOperatingAsAdmin() && $pendingReminderGurus->isNotEmpty(),
            'canSendThanks' => $user->isOperatingAsAdmin() && $allAccessibleGuruIds->isNotEmpty() && $pendingReminderGurus->isEmpty(),
            'hasPendingRequests' => $hasPendingRequests,
            'pendingReminderCount' => $pendingReminderGurus->count(),
            'isGuru' => $user->isOperatingAsGuru(),
            'guruId' => $user->guru?->id,
        ]);
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

    private function assignedPastiIds(User $user): array
    {
        return $user->assignedPastis()->pluck('pastis.id')->all();
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

    private function isTestReminderAccount(Guru $guru): bool
    {
        $displayName = trim(mb_strtolower((string) $guru->display_name));
        $guruName = trim(mb_strtolower((string) $guru->name));

        return in_array('test', [$displayName, $guruName], true);
    }
}
