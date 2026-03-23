<?php

namespace App\Livewire;

use App\Models\Pasti;
use App\Models\PastiInformationRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;

class PastiInformationIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
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
                ->whereNull('completed_at')
                ->exists();

        $pastis = (clone $accessiblePastisQuery)
            ->with('kawasan')
            ->when(
                trim($this->search) !== '',
                fn (Builder $query) => $query->where('name', 'like', '%' . trim($this->search) . '%')
            )
            ->orderBy('name')
            ->paginate(10);

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
            'canRequest' => $user->hasAnyRole(['master_admin', 'admin']),
            'canRequestAll' => $user->hasAnyRole(['master_admin', 'admin']) && ! $hasPendingRequests && $allAccessiblePastiIds->isNotEmpty(),
            'isGuru' => $user->hasRole('guru'),
            'guruPastiId' => $user->guru?->pasti_id,
        ]);
    }

    private function accessiblePastisQueryForUser(User $user): Builder
    {
        $query = Pasti::query();

        if ($user->hasRole('guru')) {
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
}
