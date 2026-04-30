<?php

namespace App\Livewire;

use App\Models\Pasti;
use App\Models\PastiInformationRequest;
use App\Models\User;
use Livewire\Attributes\Url;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;

class PastiInformationIndex extends Component
{
    use WithPagination;

    private const PAGE_NAME = 'pastiInfoPage';

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

        $accessiblePastisQuery = $this->accessiblePastisQueryForUser($user);
        $allAccessiblePastiIds = (clone $accessiblePastisQuery)->pluck('pastis.id');
        $hasPendingRequests = $allAccessiblePastiIds->isNotEmpty()
            && PastiInformationRequest::query()
                ->whereIn('pasti_id', $allAccessiblePastiIds->all())
                ->whereDoesntHave('pasti.gurus.user', function (Builder $userQuery): void {
                    $userQuery
                        ->whereRaw('lower(coalesce(name, \'\')) = ?', ['test'])
                        ->orWhereRaw('lower(coalesce(nama_samaran, \'\')) = ?', ['test']);
                })
                ->whereNull('completed_at')
                ->exists();

        $pastis = $this->pastisQueryForActiveTab($accessiblePastisQuery)
            ->paginate(9, ['*'], self::PAGE_NAME);

        $pastiIds = collect($pastis->items())->pluck('id')->all();

        $requestGroups = PastiInformationRequest::query()
            ->with(['requestedBy', 'completedBy'])
            ->whereIn('pasti_id', $pastiIds)
            ->orderByDesc('id')
            ->get()
            ->groupBy('pasti_id');

        return view('livewire.pasti-information-index', [
            'pastis' => $pastis,
            'latestRequests' => $requestGroups->map(fn (Collection $items) => $items->first()),
            'latestCompletedRequests' => $requestGroups->map(fn (Collection $items) => $items->firstWhere(fn (PastiInformationRequest $item) => $item->completed_at !== null)),
            'canRequest' => $user->isOperatingAsAdmin(),
            'canRequestAll' => $user->isOperatingAsAdmin() && ! $hasPendingRequests && $allAccessiblePastiIds->isNotEmpty(),
            'hasPendingRequests' => $hasPendingRequests,
            'isGuru' => $user->isOperatingAsGuru(),
            'guruPastiId' => $user->guru?->pasti_id,
            'activeTab' => $this->activeTab,
        ]);
    }

    private function pastisQueryForActiveTab(Builder $accessiblePastisQuery): Builder
    {
        $query = (clone $accessiblePastisQuery)
            ->select('pastis.*')
            ->selectSub(
                PastiInformationRequest::query()
                    ->selectRaw('MAX(id)')
                    ->whereColumn('pasti_information_requests.pasti_id', 'pastis.id'),
                'latest_request_id'
            )
            ->with(['kawasan', 'latestInformationRequest'])
            ->when(
                trim($this->search) !== '',
                fn (Builder $query) => $query->where('name', 'like', '%' . trim($this->search) . '%')
            );

        if ($this->activeTab === 'responded') {
            $query->whereHas('latestInformationRequest', fn (Builder $requestQuery) => $requestQuery->whereNotNull('completed_at'));
        } else {
            $query->where(function (Builder $tabQuery): void {
                $tabQuery
                    ->whereDoesntHave('latestInformationRequest')
                    ->orWhereHas('latestInformationRequest', fn (Builder $requestQuery) => $requestQuery->whereNull('completed_at'));
            });
        }

        return $query
            ->orderByRaw('COALESCE(latest_request_id, pastis.id) DESC')
            ->orderBy('name');
    }

    private function accessiblePastisQueryForUser(User $user): Builder
    {
        $query = Pasti::query();

        if ($user->isOperatingAsGuru()) {
            $query->whereKey($user->guru?->pasti_id ?: 0);
        } elseif ($user->hasRole('admin') && ! $user->hasRole('master_admin')) {
            $query->whereIn('id', $this->assignedPastiIds($user));
        }

        return $query;
    }

    private function assignedPastiIds(User $user): array
    {
        return $user->assignedPastis()->pluck('pastis.id')->all();
    }

    /**
     * @return array<int, string>
     */
    private function allowedTabs(): array
    {
        return ['pending', 'responded'];
    }
}
