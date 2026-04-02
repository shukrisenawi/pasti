<?php

namespace App\Livewire;

use App\Models\AjkPosition;
use App\Models\User;
use App\Notifications\AjkPositionUpdatedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class AjkProgramManager extends Component
{
    public string $activeTab = 'assignments';

    public ?int $selectedUserId = null;

    public array $positionIds = [];

    public ?int $editingPositionId = null;

    public string $positionName = '';

    public string $positionDescription = '';

    public ?string $notice = null;

    public function mount(): void
    {
        $requestedTab = (string) request()->query('tab', 'assignments');
        $this->activeTab = in_array($requestedTab, ['assignments', 'positions'], true)
            ? $requestedTab
            : 'assignments';

        if ($this->activeTab === 'positions' && ! $this->canManagePositions()) {
            $this->activeTab = 'assignments';
        }

        $requestedUserId = (int) request()->query('selected_user_id', 0);
        if ($requestedUserId > 0) {
            $this->selectedUserId = $requestedUserId;
        }

        $this->ensureSelectedUser();
    }

    public function render()
    {
        $this->ensureAccess();

        $users = User::query()
            ->with(['roles', 'ajkPositions', 'guru'])
            ->orderByRaw("COALESCE(NULLIF(nama_samaran, ''), name)")
            ->get();

        $positions = AjkPosition::query()->orderBy('name')->get();
        $selectedUser = $users->firstWhere('id', $this->selectedUserId);

        if (! $selectedUser) {
            $selectedUser = $users->first();
            $this->selectedUserId = $selectedUser?->id;
        }

        if ($selectedUser && $this->positionIds === []) {
            $this->positionIds = $selectedUser->ajkPositions->pluck('id')->map(fn ($id) => (int) $id)->all();
        }

        return view('livewire.ajk-program-manager', [
            'positions' => $positions,
            'users' => $users,
            'selectedUser' => $selectedUser,
            'canManagePositions' => $this->canManagePositions(),
        ]);
    }

    public function switchTab(string $tab): void
    {
        if ($tab === 'positions' && ! $this->canManagePositions()) {
            return;
        }

        if (! in_array($tab, ['assignments', 'positions'], true)) {
            return;
        }

        $this->activeTab = $tab;
        $this->cancelEditPosition();
    }

    public function updatedSelectedUserId($value): void
    {
        $userId = (int) $value;
        $user = User::query()->with('ajkPositions')->find($userId);

        if (! $user) {
            $this->positionIds = [];

            return;
        }

        $this->selectedUserId = $user->id;
        $this->positionIds = $user->ajkPositions->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    public function selectUser($value): void
    {
        $this->updatedSelectedUserId($value);
    }

    public function saveAssignments(): void
    {
        $this->ensureAccess();

        $actor = auth()->user();
        $user = User::query()->with('ajkPositions')->find($this->selectedUserId ?? 0);

        if (! $user) {
            $this->addError('selectedUserId', __('messages.select_user'));

            return;
        }

        if ($actor->hasRole('admin') && ! $actor->hasRole('master_admin') && $user->hasRole('master_admin')) {
            abort(403);
        }

        $newPositionIds = collect($this->positionIds)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $validated = validator(
            ['position_ids' => $newPositionIds],
            [
                'position_ids' => ['nullable', 'array'],
                'position_ids.*' => ['integer', 'exists:ajk_positions,id'],
            ]
        )->validate();

        $newPositionIds = collect($validated['position_ids'] ?? [])->map(fn ($id) => (int) $id)->all();

        $currentPositionIds = $user->ajkPositions->pluck('id')->map(fn ($id) => (int) $id)->all();
        $addedIds = array_values(array_diff($newPositionIds, $currentPositionIds));
        $removedIds = array_values(array_diff($currentPositionIds, $newPositionIds));

        $syncPayload = [];
        foreach ($newPositionIds as $positionId) {
            $syncPayload[$positionId] = ['assigned_by' => $actor->id];
        }

        DB::transaction(function () use ($user, $syncPayload): void {
            $user->ajkPositions()->sync($syncPayload);
        });

        if ($addedIds !== [] || $removedIds !== []) {
            $addedNames = AjkPosition::query()->whereIn('id', $addedIds)->orderBy('name')->pluck('name')->all();
            $removedNames = AjkPosition::query()->whereIn('id', $removedIds)->orderBy('name')->pluck('name')->all();

            $user->notify(new AjkPositionUpdatedNotification($actor, $addedNames, $removedNames));
        }

        $this->notice = __('messages.saved');
    }

    public function editPosition(int $positionId): void
    {
        if (! $this->canManagePositions()) {
            abort(403);
        }

        $position = AjkPosition::query()->findOrFail($positionId);
        $this->editingPositionId = $position->id;
        $this->positionName = $position->name;
        $this->positionDescription = $position->description;
    }

    public function cancelEditPosition(): void
    {
        $this->editingPositionId = null;
        $this->positionName = '';
        $this->positionDescription = '';
    }

    public function savePosition(): void
    {
        if (! $this->canManagePositions()) {
            abort(403);
        }

        $validated = $this->validate([
            'positionName' => [
                'required',
                'string',
                'max:255',
                Rule::unique('ajk_positions', 'name')->ignore($this->editingPositionId),
            ],
            'positionDescription' => ['required', 'string', 'max:1000'],
        ]);

        if ($this->editingPositionId) {
            AjkPosition::query()->whereKey($this->editingPositionId)->update([
                'name' => $validated['positionName'],
                'description' => $validated['positionDescription'],
            ]);
        } else {
            AjkPosition::query()->create([
                'name' => $validated['positionName'],
                'description' => $validated['positionDescription'],
            ]);
        }

        $this->cancelEditPosition();
        $this->notice = __('messages.saved');
    }

    public function deletePosition(int $positionId): void
    {
        if (! $this->canManagePositions()) {
            abort(403);
        }

        AjkPosition::query()->whereKey($positionId)->delete();
        $this->notice = __('messages.deleted');

        if ($this->editingPositionId === $positionId) {
            $this->cancelEditPosition();
        }
    }

    private function ensureAccess(): void
    {
        abort_unless(auth()->user()?->hasAnyRole(['master_admin', 'admin']), 403);
    }

    private function canManagePositions(): bool
    {
        return auth()->user()?->hasRole('master_admin') ?? false;
    }

    private function ensureSelectedUser(): void
    {
        if ($this->selectedUserId) {
            return;
        }

        $this->selectedUserId = User::query()
            ->orderByRaw("COALESCE(NULLIF(nama_samaran, ''), name)")
            ->value('id');
    }
}


