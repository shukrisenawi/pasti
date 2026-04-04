<?php

namespace App\Http\Controllers;

use App\Models\LeaveNotice;
use App\Models\User;
use App\Notifications\LeaveNoticeSubmittedNotification;
use App\Services\N8nWebhookService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class LeaveNoticeController extends Controller
{
    public function __construct(
        private readonly N8nWebhookService $n8nWebhookService,
    ) {
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $query = LeaveNotice::query()->with(['guru.user', 'guru.pasti']);
        $canDeleteAll = $user->hasRole('master_admin');
        $currentGuruId = null;
        $assignedPastiIds = [];

        if ($user->hasRole('guru')) {
            $guruId = $user->guru?->id;
            abort_unless($guruId, 403);
            $query->where('guru_id', $guruId);
            $currentGuruId = $guruId;
        } elseif ($user->hasRole('admin')) {
            $assignedPastiIds = $this->assignedPastiIds($user);
            $query->whereHas('guru', fn ($q) => $q->whereIn('pasti_id', $assignedPastiIds));
        } elseif (! $user->hasRole('master_admin')) {
            abort(403);
        }

        return view('leave-notices.index', [
            'leaveNotices' => $query->latest('leave_date')->latest('id')->paginate(10),
            'showAdminColumns' => $user->hasAnyRole(['master_admin', 'admin']),
            'canDeleteAll' => $canDeleteAll,
            'currentGuruId' => $currentGuruId,
            'assignedPastiIds' => $assignedPastiIds,
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->hasRole('guru'), 403);

        return view('leave-notices.form');
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasRole('guru'), 403);

        $guruId = $user->guru?->id;
        abort_unless($guruId, 403);

        $data = $request->validate([
            'leave_date' => ['required', 'date'],
            'leave_until' => ['required', 'date'],
            'reason' => ['required', 'string'],
            'mc_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $leaveNotice = LeaveNotice::query()->create([
            'guru_id' => $guruId,
            'leave_date' => $data['leave_date'],
            'leave_until' => $data['leave_until'],
            'reason' => $data['reason'],
        ]);

        if ($request->hasFile('mc_image')) {
            $leaveNotice->update([
                'mc_image_path' => $request->file('mc_image')->store('leave-mc', 'public'),
            ]);
        }

        $leaveNotice->loadMissing(['guru.user', 'guru.pasti']);

        $masterAdmins = User::role('master_admin')->get();
        $relatedAdmins = User::role('admin')
            ->whereHas('assignedPastis', fn ($q) => $q->whereKey($leaveNotice->guru?->pasti_id))
            ->get();
        $recipients = $masterAdmins->merge($relatedAdmins)->unique('id')->values();

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new LeaveNoticeSubmittedNotification($leaveNotice));
        }

        $this->n8nWebhookService->sendGroup2ByTemplate(
            N8nWebhookService::KEY_TEXT_LEAVE_NOTICE_SUBMITTED,
            [
                'nama_guru' => (string) ($leaveNotice->guru?->display_name ?? $user->display_name),
                'tarikh_cuti' => optional($leaveNotice->leave_date)->format('d/m/Y') ?? (string) $data['leave_date'],
                'tarikh_hingga' => optional($leaveNotice->leave_until)->format('d/m/Y') ?? (string) $data['leave_until'],
                'sebab' => trim((string) $leaveNotice->reason),
            ],
            $this->n8nWebhookService->toPublicUrl(route('leave-notices.index')),
            $this->n8nWebhookService->toPublicUrl($leaveNotice->mc_image_url)
        );

        return redirect()->route('leave-notices.index')->with('status', __('messages.saved'));
    }

    public function destroy(Request $request, LeaveNotice $leaveNotice): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasRole('guru')) {
            abort_unless((int) ($user->guru?->id) === (int) $leaveNotice->guru_id, 403);
        } elseif ($user->hasRole('admin')) {
            $assignedPastiIds = $this->assignedPastiIds($user);
            abort_unless(
                $leaveNotice->guru()->whereIn('pasti_id', $assignedPastiIds)->exists(),
                403
            );
        } elseif (! $user->hasRole('master_admin')) {
            abort(403);
        }

        if ($leaveNotice->mc_image_path) {
            Storage::disk('public')->delete($leaveNotice->mc_image_path);
        }

        $leaveNotice->delete();

        return redirect()->route('leave-notices.index')->with('status', __('messages.deleted'));
    }
}
