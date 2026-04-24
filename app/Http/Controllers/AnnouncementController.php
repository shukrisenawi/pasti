<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Guru;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->hasAnyRole(['master_admin', 'admin']), 403);

        $query = Announcement::query()
            ->withCount('recipients')
            ->with('sender')
            ->latest('id');

        if ($user->hasRole('admin') && ! $user->hasRole('master_admin')) {
            $query->where('sent_by', $user->id);
        }

        return view('announcements.index', [
            'announcements' => $query->paginate(10),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasAnyRole(['master_admin', 'admin']), 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:3000'],
            'expires_at' => ['required', 'date', 'after_or_equal:today'],
        ]);

        $recipientUserIds = $this->recipientGuruUsers($user)->pluck('id')->map(fn ($id) => (int) $id)->all();

        if ($recipientUserIds === []) {
            return back()->withErrors([
                'title' => 'Tiada guru aktif dijumpai untuk terima pengumuman ini.',
            ])->withInput();
        }

        DB::transaction(function () use ($data, $user, $recipientUserIds): void {
            $announcement = Announcement::query()->create([
                'title' => trim((string) $data['title']),
                'body' => trim((string) $data['body']),
                'expires_at' => $data['expires_at'],
                'sent_by' => $user->id,
            ]);

            $announcement->recipients()->sync($recipientUserIds);
        });

        return redirect()->route('announcements.index')->with('status', __('messages.saved'));
    }

    public function destroy(Request $request, Announcement $announcement): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasAnyRole(['master_admin', 'admin']), 403);

        if ($user->hasRole('admin') && ! $user->hasRole('master_admin')) {
            abort_unless((int) $announcement->sent_by === (int) $user->id, 403);
        }

        $announcement->delete();

        return redirect()->route('announcements.index')->with('status', __('messages.deleted'));
    }

    private function recipientGuruUsers(User $user)
    {
        $query = Guru::query()
            ->where('active', true)
            ->where('is_assistant', false)
            ->whereNotNull('user_id')
            ->with('user');

        if ($user->hasRole('admin') && ! $user->hasRole('master_admin')) {
            $query->whereIn('pasti_id', $this->assignedPastiIds($user));
        }

        return User::query()
            ->whereIn('id', $query->pluck('user_id'))
            ->whereHas('roles', fn (Builder $roleQuery) => $roleQuery->where('name', 'guru'))
            ->get();
    }
}

