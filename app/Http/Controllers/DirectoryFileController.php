<?php

namespace App\Http\Controllers;

use App\Models\DirectoryFile;
use App\Models\Guru;
use App\Models\User;
use App\Notifications\DirectoryFileAssignedNotification;
use App\Services\N8nWebhookService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DirectoryFileController extends Controller
{
    public function __construct(
        private readonly N8nWebhookService $n8nWebhookService,
    ) {
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->hasAnyRole(['master_admin', 'admin', 'guru']), 403);

        $filesQuery = DirectoryFile::query()
            ->with(['uploader', 'recipients.user', 'recipients.pasti'])
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($user->hasRole('guru')) {
            $guruId = (int) ($user->guru?->id ?? 0);
            abort_unless($guruId > 0, 403);

            $filesQuery->where(function (Builder $query) use ($guruId): void {
                $query->where('target_type', 'all')
                    ->orWhereHas('recipients', fn (Builder $recipientQuery) => $recipientQuery->whereKey($guruId));
            });
        } elseif ($user->hasRole('admin') && ! $user->hasRole('master_admin')) {
            $assignedPastiIds = $this->assignedPastiIds($user);

            $filesQuery->where(function (Builder $query) use ($user, $assignedPastiIds): void {
                $query->where('uploaded_by', $user->id)
                    ->orWhere('target_type', 'all')
                    ->orWhereHas(
                        'recipients',
                        fn (Builder $recipientQuery) => $recipientQuery->whereIn('pasti_id', $assignedPastiIds)
                    );
            });
        }

        $canUpload = $user->hasAnyRole(['master_admin', 'admin']);

        return view('directory-files.index', [
            'files' => $filesQuery->paginate(10),
            'canUpload' => $canUpload,
            'availableGurus' => $canUpload ? $this->availableGurusForUploader($user) : collect(),
            'isGuruOnly' => $user->hasRole('guru') && ! $user->hasAnyRole(['master_admin', 'admin']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasAnyRole(['master_admin', 'admin']), 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'target_type' => ['required', 'in:all,selected'],
            'notify_group_guru' => ['nullable', 'boolean'],
            'guru_ids' => ['nullable', 'array', 'required_if:target_type,selected'],
            'guru_ids.*' => ['integer', 'exists:gurus,id'],
            'attachment' => ['required', 'file', 'max:20480'],
        ], [
            'guru_ids.required_if' => 'Sila pilih sekurang-kurangnya seorang guru.',
        ]);

        $availableGurus = $this->availableGurusForUploader($user);
        $allowedGuruIds = $availableGurus->pluck('id')->map(fn ($id) => (int) $id)->all();
        $selectedGuruIds = collect($data['guru_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values()->all();

        if (($data['target_type'] ?? 'all') === 'selected') {
            abort_unless($selectedGuruIds !== [], 422);

            $invalidGuruIds = array_diff($selectedGuruIds, $allowedGuruIds);
            abort_if($invalidGuruIds !== [], 403);
        }

        if (($data['target_type'] ?? 'all') === 'all' && $allowedGuruIds === []) {
            return back()->withErrors([
                'target_type' => 'Tiada guru aktif dalam akses anda untuk menerima fail ini.',
            ])->withInput();
        }

        $directoryFile = DB::transaction(function () use ($request, $user, $data, $selectedGuruIds): DirectoryFile {
            $storedPath = $request->file('attachment')->store('directory-files', 'public');

            $directoryFile = DirectoryFile::query()->create([
                'title' => trim((string) $data['title']),
                'target_type' => (string) $data['target_type'],
                'original_name' => (string) $request->file('attachment')->getClientOriginalName(),
                'file_path' => $storedPath,
                'uploaded_by' => $user->id,
            ]);

            if ($directoryFile->target_type === 'selected') {
                $directoryFile->recipients()->sync($selectedGuruIds);
            }

            return $directoryFile;
        });

        $recipientUsers = $directoryFile->target_type === 'all'
            ? $availableGurus
                ->map(fn (Guru $guru) => $guru->user)
                ->filter()
                ->unique('id')
                ->values()
            : $availableGurus
                ->filter(fn (Guru $guru) => in_array((int) $guru->id, $selectedGuruIds, true))
                ->map(fn (Guru $guru) => $guru->user)
                ->filter()
                ->unique('id')
                ->values();

        if ($recipientUsers->isNotEmpty()) {
            Notification::send($recipientUsers, new DirectoryFileAssignedNotification($directoryFile, $user));
        }

        $shouldNotifyGroupGuru = ($directoryFile->target_type === 'all')
            && ((int) ($data['notify_group_guru'] ?? 1) === 1);

        if ($shouldNotifyGroupGuru) {
            $this->n8nWebhookService->sendByTemplate(
                N8nWebhookService::KEY_TEXT_DIRECTORY_FILE_ALL_GURU,
                [
                    'nama_penghantar' => $user->display_name,
                    'nama_fail' => $directoryFile->title,
                ],
                $this->n8nWebhookService->toActionUrl(route('directory-files.index'))
            );
        }

        return redirect()->route('directory-files.index')->with('status', __('messages.saved'));
    }

    public function download(Request $request, DirectoryFile $directoryFile)
    {
        $user = $request->user();
        abort_unless($user->hasAnyRole(['master_admin', 'admin', 'guru']), 403);
        abort_unless($this->canAccessFile($user, $directoryFile), 403);

        abort_unless(Storage::disk('public')->exists($directoryFile->file_path), 404);

        return Storage::disk('public')->download($directoryFile->file_path, $directoryFile->original_name);
    }

    public function destroy(Request $request, DirectoryFile $directoryFile): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasAnyRole(['master_admin', 'admin']), 403);

        if (! $user->hasRole('master_admin') && (int) $directoryFile->uploaded_by !== (int) $user->id) {
            abort(403);
        }

        if ($directoryFile->file_path && Storage::disk('public')->exists($directoryFile->file_path)) {
            Storage::disk('public')->delete($directoryFile->file_path);
        }

        $directoryFile->delete();

        return redirect()->route('directory-files.index')->with('status', __('messages.deleted'));
    }

    private function availableGurusForUploader(User $user)
    {
        return Guru::query()
            ->with(['user', 'pasti'])
            ->where('active', true)
            ->where('is_assistant', false)
            ->whereNotNull('user_id')
            ->when(
                $user->hasRole('admin') && ! $user->hasRole('master_admin'),
                fn (Builder $query) => $query->whereIn('pasti_id', $this->assignedPastiIds($user))
            )
            ->orderBy('name')
            ->get();
    }

    private function canAccessFile(User $user, DirectoryFile $directoryFile): bool
    {
        if ($user->hasRole('master_admin')) {
            return true;
        }

        if ($user->hasRole('admin')) {
            if ((int) $directoryFile->uploaded_by === (int) $user->id || $directoryFile->target_type === 'all') {
                return true;
            }

            $assignedPastiIds = $this->assignedPastiIds($user);
            if ($assignedPastiIds === []) {
                return false;
            }

            return $directoryFile->recipients()
                ->whereIn('pasti_id', $assignedPastiIds)
                ->exists();
        }

        $guruId = (int) ($user->guru?->id ?? 0);
        if ($guruId === 0) {
            return false;
        }

        if ($directoryFile->target_type === 'all') {
            return true;
        }

        return $directoryFile->recipients()->whereKey($guruId)->exists();
    }
}
