<?php

namespace App\Livewire;

use App\Models\Program;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class ProgramIndex extends Component
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

        $query = Program::query()
            ->when(
                $this->isGuruOnly($user),
                fn (Builder $q) => $q->whereHas('gurus', fn ($gq) => $gq->where('gurus.id', $user->guru?->id ?? 0))
            )
            ->when(
                trim($this->search) !== '',
                fn (Builder $q) => $q->where(function (Builder $searchQuery): void {
                    $keyword = '%' . trim($this->search) . '%';
                    $searchQuery
                        ->where('title', 'like', $keyword)
                        ->orWhere('location', 'like', $keyword);
                })
            )
            ->latest('program_date');

        return view('livewire.program-index', [
            'programs' => $query->paginate(9),
            'canManageProgram' => $user->hasAnyRole(['master_admin', 'admin']),
        ]);
    }

    private function isGuruOnly(User $user): bool
    {
        return $user->hasRole('guru') && ! $user->hasAnyRole(['master_admin', 'admin']);
    }
}

