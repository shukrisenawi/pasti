<?php

namespace App\Http\Controllers;

use App\Models\AjkPosition;
use App\Models\User;
use App\Notifications\AjkPositionUpdatedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AjkProgramController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->hasAnyRole(['master_admin', 'admin']), 403);

        return view('ajk-program.index');
    }

    public function updateAssignments(Request $request): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor->hasAnyRole(['master_admin', 'admin']), 403);

        $validated = $request->validate([
            'selected_user_ids' => ['required', 'array', 'min:1'],
            'selected_user_ids.*' => ['integer', 'exists:users,id'],
            'position_ids' => ['nullable', 'array'],
            'position_ids.*' => ['integer', 'exists:ajk_positions,id'],
        ]);

        $targetUserIds = collect($validated['selected_user_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $targetUsers = User::query()->with('ajkPositions')->whereIn('id', $targetUserIds)->get();

        if ($targetUsers->isEmpty()) {
            return redirect()->route('ajk-program.index')->with('status', __('messages.saved'));
        }

        if (
            $actor->hasRole('admin')
            && ! $actor->hasRole('master_admin')
            && $targetUsers->contains(fn (User $user) => $user->hasRole('master_admin'))
        ) {
            abort(403);
        }

        $newPositionIds = collect($validated['position_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $syncPayload = [];
        foreach ($newPositionIds as $positionId) {
            $syncPayload[$positionId] = ['assigned_by' => $actor->id];
        }

        foreach ($targetUsers as $targetUser) {
            $currentPositionIds = $targetUser->ajkPositions->pluck('id')->map(fn ($id) => (int) $id)->all();
            $addedIds = array_values(array_diff($newPositionIds, $currentPositionIds));
            $removedIds = array_values(array_diff($currentPositionIds, $newPositionIds));

            DB::transaction(function () use ($targetUser, $syncPayload): void {
                $targetUser->ajkPositions()->sync($syncPayload);
            });

            if ($addedIds !== [] || $removedIds !== []) {
                $addedNames = AjkPosition::query()->whereIn('id', $addedIds)->orderBy('name')->pluck('name')->all();
                $removedNames = AjkPosition::query()->whereIn('id', $removedIds)->orderBy('name')->pluck('name')->all();

                $targetUser->notify(new AjkPositionUpdatedNotification($actor, $addedNames, $removedNames));
            }
        }

        return redirect()
            ->route('ajk-program.index', ['selected_user_id' => $targetUsers->first()->id])
            ->with('status', __('messages.saved'));
    }
}

