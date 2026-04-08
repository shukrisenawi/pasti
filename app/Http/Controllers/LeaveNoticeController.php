<?php

namespace App\Http\Controllers;

use App\Models\LeaveNotice;
use App\Models\User;
use App\Notifications\LeaveNoticeSubmittedNotification;
use App\Services\KpiCalculationService;
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
        private readonly KpiCalculationService $kpiCalculationService,
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

        return view('leave-notices.form', [
            'leaveNotice' => new LeaveNotice(),
            'formAction' => route('leave-notices.store'),
            'formMethod' => 'POST',
        ]);
    }

    public function edit(Request $request, LeaveNotice $leaveNotice): View
    {
        $this->ensureCanManageNotice($request, $leaveNotice);

        return view('leave-notices.form', [
            'leaveNotice' => $leaveNotice,
            'formAction' => route('leave-notices.update', $leaveNotice),
            'formMethod' => 'PUT',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasRole('guru'), 403);

        $guruId = $user->guru?->id;
        abort_unless($guruId, 403);

        $data = $request->validate([
            'leave_date' => ['required', 'date'],
            'leave_until' => ['required', 'date', 'after_or_equal:leave_date'],
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

        if ($leaveNotice->guru) {
            $this->kpiCalculationService->recalculateForGuru($leaveNotice->guru);
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

    public function update(Request $request, LeaveNotice $leaveNotice): RedirectResponse
    {
        $this->ensureCanManageNotice($request, $leaveNotice);

        $data = $request->validate([
            'leave_date' => ['required', 'date'],
            'leave_until' => ['required', 'date', 'after_or_equal:leave_date'],
            'reason' => ['required', 'string'],
            'mc_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'remove_mc_image' => ['nullable', 'boolean'],
        ]);

        $leaveNotice->update([
            'leave_date' => $data['leave_date'],
            'leave_until' => $data['leave_until'],
            'reason' => $data['reason'],
        ]);

        if ($request->hasFile('mc_image')) {
            if ($leaveNotice->mc_image_path) {
                Storage::disk('public')->delete($leaveNotice->mc_image_path);
            }

            $leaveNotice->update([
                'mc_image_path' => $request->file('mc_image')->store('leave-mc', 'public'),
            ]);
        } elseif ($request->boolean('remove_mc_image') && $leaveNotice->mc_image_path) {
            Storage::disk('public')->delete($leaveNotice->mc_image_path);
            $leaveNotice->update(['mc_image_path' => null]);
        }

        $leaveNotice->loadMissing('guru');
        if ($leaveNotice->guru) {
            $this->kpiCalculationService->recalculateForGuru($leaveNotice->guru);
        }

        return redirect()->route('leave-notices.index')->with('status', __('messages.saved'));
    }

    public function destroy(Request $request, LeaveNotice $leaveNotice): RedirectResponse
    {
        $this->ensureCanManageNotice($request, $leaveNotice);
        $leaveNotice->loadMissing('guru');
        $guru = $leaveNotice->guru;

        if ($leaveNotice->mc_image_path) {
            Storage::disk('public')->delete($leaveNotice->mc_image_path);
        }

        $leaveNotice->delete();

        if ($guru) {
            $this->kpiCalculationService->recalculateForGuru($guru);
        }

        return redirect()->route('leave-notices.index')->with('status', __('messages.deleted'));
    }

    private function ensureCanManageNotice(Request $request, LeaveNotice $leaveNotice): void
    {
        $user = $request->user();

        if ($user->hasRole('master_admin')) {
            return;
        }

        if ($user->hasRole('admin')) {
            $assignedPastiIds = $this->assignedPastiIds($user);
            abort_unless(
                $leaveNotice->guru()->whereIn('pasti_id', $assignedPastiIds)->exists(),
                403
            );

            return;
        }

        abort(403);
    }
}

