<?php

namespace App\Http\Controllers;

use App\Models\AjkPosition;
use App\Models\User;
use App\Notifications\AjkPositionUpdatedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AjkProgramController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->hasAnyRole(['master_admin', 'admin']), 403);

        $activeTab = $request->query('tab', 'assignments');
        $availableTabs = $user->hasRole('master_admin')
            ? ['assignments', 'positions']
            : ['assignments'];
        if (! in_array($activeTab, $availableTabs, true)) {
            $activeTab = 'assignments';
        }

        $positions = AjkPosition::query()->orderBy('name')->get();
        $users = User::query()
            ->with(['roles', 'ajkPositions'])
            ->orderByRaw('COALESCE(NULLIF(nama_samaran, \'\'), name)')
            ->get();

        $selectedUser = null;
        $selectedUserId = (int) $request->integer('user_id');
        if ($selectedUserId > 0) {
            $selectedUser = $users->firstWhere('id', $selectedUserId);
        }
        if (! $selectedUser) {
            $selectedUser = $users->first();
        }

        $editingPosition = null;
        if ($request->filled('edit_position') && $user->hasRole('master_admin')) {
            $editingPosition = AjkPosition::query()->find($request->integer('edit_position'));
        }

        return view('ajk-program.index', [
            'positions' => $positions,
            'users' => $users,
            'selectedUser' => $selectedUser,
            'editingPosition' => $editingPosition,
            'activeTab' => $activeTab,
        ]);
    }

    public function storePosition(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasRole('master_admin'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:ajk_positions,name'],
            'description' => ['required', 'string', 'max:1000'],
        ]);

        AjkPosition::query()->create($data);

        return redirect()->route('ajk-program.index', ['tab' => 'positions'])->with('status', __('messages.saved'));
    }

    public function updatePosition(Request $request, AjkPosition $position): RedirectResponse
    {
        abort_unless($request->user()->hasRole('master_admin'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('ajk_positions', 'name')->ignore($position->id)],
            'description' => ['required', 'string', 'max:1000'],
        ]);

        $position->update($data);

        return redirect()->route('ajk-program.index', ['tab' => 'positions'])->with('status', __('messages.saved'));
    }

    public function destroyPosition(Request $request, AjkPosition $position): RedirectResponse
    {
        abort_unless($request->user()->hasRole('master_admin'), 403);

        $position->delete();

        return redirect()->route('ajk-program.index', ['tab' => 'positions'])->with('status', __('messages.deleted'));
    }

    public function updateAssignments(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor->hasAnyRole(['master_admin', 'admin']), 403);

        if ($actor->hasRole('admin') && ! $actor->hasRole('master_admin') && $user->hasRole('master_admin')) {
            abort(403);
        }

        $data = $request->validate([
            'position_ids' => ['nullable', 'array'],
            'position_ids.*' => ['integer', 'exists:ajk_positions,id'],
        ]);

        $newPositionIds = collect($data['position_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $currentPositionIds = $user->ajkPositions()->pluck('ajk_positions.id')->map(fn ($id) => (int) $id)->all();
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

        return redirect()
            ->route('ajk-program.index', ['user_id' => $user->id, 'tab' => 'assignments'])
            ->with('status', __('messages.saved'));
    }
}
