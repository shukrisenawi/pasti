<?php

namespace App\Livewire;

use App\Models\Guru;
use App\Models\GuruSalaryRequest;
use App\Models\User;
use Livewire\Attributes\Url;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;

class GuruSalaryInformationIndex extends Component
{
    use WithPagination;

    private const PAGE_NAME = 'guruSalaryPage';

    #[Url(as: 'tab', except: 'pending')]
    public string $activeTab = 'pending';

    public string $search = '';

    public function mount(): void
    {
        if (! in_array($this->activeTab, $this->allowedTabs(), true)) {
            $this->activeTab = 'pending';
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage(self::PAGE_NAME);
    }

    public function updatedActiveTab(): void
    {
        if (! in_array($this->activeTab, $this->allowedTabs(), true)) {
            $this->activeTab = 'pending';
        }

        $this->resetPage(self::PAGE_NAME);
    }

    public function switchTab(string $tab): void
    {
        if (! in_array($tab, $this->allowedTabs(), true)) {
            return;
        }

        if ($this->activeTab !== $tab) {
            $this->activeTab = $tab;
        }
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

        $gurus = $this->gurusQueryForActiveTab($accessibleGurusQuery)
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
            'hasPendingRequests' => $hasPendingRequests,
            'pendingReminderCount' => $pendingReminderGurus->count(),
            'isGuru' => $user->isOperatingAsGuru(),
            'guruId' => $user->guru?->id,
            'activeTab' => $this->activeTab,
        ]);
    }

    private function gurusQueryForActiveTab(Builder $accessibleGurusQuery): Builder
    {
        $query = (clone $accessibleGurusQuery)
            ->select('gurus.*')
            ->selectSub(
                GuruSalaryRequest::query()
                    ->selectRaw('MAX(id)')
                    ->whereColumn('guru_salary_requests.guru_id', 'gurus.id'),
                'latest_request_id'
            )
            ->selectSub(
                GuruSalaryRequest::query()
                    ->selectRaw('MAX(completed_at)')
                    ->whereColumn('guru_salary_requests.guru_id', 'gurus.id'),
                'latest_response_at'
            )
            ->with(['pasti', 'user', 'latestSalaryRequest'])
            ->when(
                trim($this->search) !== '',
                fn (Builder $query) => $query->where(function (Builder $searchQuery): void {
                    $keyword = '%' . trim($this->search) . '%';
                    $searchQuery
                        ->where('gurus.name', 'like', $keyword)
                        ->orWhereHas('pasti', fn (Builder $pastiQuery) => $pastiQuery->where('name', 'like', $keyword));
                })
            )
            ->leftJoin('pastis', 'pastis.id', '=', 'gurus.pasti_id');

        if ($this->activeTab === 'responded') {
            $query->whereHas('latestSalaryRequest', fn (Builder $salaryRequestQuery) => $salaryRequestQuery->whereNotNull('completed_at'));
        } else {
            $query->where(function (Builder $tabQuery): void {
                $tabQuery
                    ->whereDoesntHave('latestSalaryRequest')
                    ->orWhereHas('latestSalaryRequest', fn (Builder $salaryRequestQuery) => $salaryRequestQuery->whereNull('completed_at'));
            });
        }

        return $query
            ->orderByRaw('COALESCE(latest_request_id, gurus.id) DESC')
            ->orderBy('gurus.name');
    }

    private function accessibleGurusQueryForUser(User $user): Builder
    {
        $query = Guru::query()
            ->where('is_assistant', false)
            ->where('active', true)
            ->whereNotNull('user_id')
            ->whereDoesntHave('user', fn (Builder $userQuery) => $userQuery->where(function (Builder $nameQuery): void {
                $nameQuery
                    ->whereRaw('lower(coalesce(name, \'\')) = ?', ['test'])
                    ->orWhereRaw('lower(coalesce(nama_samaran, \'\')) = ?', ['test']);
            }));

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

    /**
     * @return array<int, string>
     */
    private function allowedTabs(): array
    {
        return ['pending', 'responded'];
    }
}
