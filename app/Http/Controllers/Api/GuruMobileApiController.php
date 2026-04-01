<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Guru;
use App\Models\LeaveNotice;
use App\Models\Pasti;
use App\Models\Program;
use App\Models\ProgramStatus;
use App\Models\User;
use App\Notifications\LeaveNoticeSubmittedNotification;
use App\Notifications\ProgramAbsenceReasonSubmittedNotification;
use App\Services\KpiCalculationService;
use App\Services\ProgramParticipationService;
use App\Support\GuruProfileCompletionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class GuruMobileApiController extends Controller
{
    public function __construct(
        private readonly KpiCalculationService $kpiCalculationService,
        private readonly ProgramParticipationService $participationService,
        private readonly GuruProfileCompletionService $profileCompletionService
    ) {
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        if (! $user->hasRole('guru')) {
            return response()->json(['message' => 'Unauthorized. Only gurus can log in here.'], 403);
        }

        $missingFields = $this->profileCompletionService->missingFields($user);
        $token = $user->createToken($request->device_name)->plainTextToken;

        return response()->json([
            'token' => $token,
            'profile_completed' => $missingFields === [],
            'missing_fields' => $missingFields,
            'missing_profile_fields' => $missingFields,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'nama_samaran' => $user->nama_samaran,
                'email' => $user->email,
                'avatar_url' => $this->assetUrl($user->avatar_url),
                'pasti' => $user->guru?->pasti?->name,
                'kawasan' => $user->guru?->pasti?->kawasan?->name,
            ],
        ]);
    }

    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();
        $guru = $user->guru;

        if (!$guru) {
            return response()->json(['message' => 'Guru profile not found.'], 404);
        }

        $guru->load(['pasti.kawasan', 'kpiSnapshot']);
        $missingFields = $this->profileCompletionService->missingFields($user);

        return response()->json([
            'profile_completed' => $missingFields === [],
            'missing_fields' => $missingFields,
            'missing_profile_fields' => $missingFields,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'nama_samaran' => $user->nama_samaran,
                'email' => $user->email,
                'tarikh_lahir' => $user->tarikh_lahir?->toDateString(),
                'tarikh_exp_skim_pas' => $user->tarikh_exp_skim_pas?->toDateString(),
                'avatar_url' => $this->assetUrl($user->avatar_url),
            ],
            'guru' => [
                'phone' => $guru->phone,
                'joined_at' => $guru->joined_at?->toDateString(),
                'pasti_id' => $guru->pasti_id,
                'pasti_name' => $guru->pasti?->name,
                'kawasan_name' => $guru->pasti?->kawasan?->name,
                'marital_status' => $guru->marital_status,
                'kpi_score' => (float) ($guru->kpiSnapshot?->score ?? 0),
            ],
        ]);
    }

    public function kpi(Request $request): JsonResponse
    {
        $user = $request->user();
        $guru = $user->guru;

        if (!$guru) {
            return response()->json(['message' => 'Guru profile not found.'], 404);
        }

        $currentYear = (int) now()->year;
        $this->kpiCalculationService->recalculateForGuru($guru);

        $guru->load([
            'kpiSnapshot',
            'programs' => fn ($q) => $q
                ->whereYear('programs.program_date', $currentYear)
                ->orderByDesc('program_date'),
        ]);

        $leaveDays = Guru::query()
            ->whereKey($guru->id)
            ->withLeaveDaysForYear($currentYear)
            ->value('leave_notices_current_year_count');

        $statusMap = ProgramStatus::query()
            ->whereIn('code', ['HADIR', 'TIDAK_HADIR'])
            ->get()
            ->keyBy('id');

        return response()->json([
            'year' => $currentYear,
            'kpi' => [
                'total_invited' => (int) ($guru->kpiSnapshot?->total_invited ?? 0),
                'total_hadir' => (int) ($guru->kpiSnapshot?->total_hadir ?? 0),
                'score' => (float) ($guru->kpiSnapshot?->score ?? 0),
                'calculated_at' => $guru->kpiSnapshot?->calculated_at?->toIso8601String(),
            ],
            'leave_days_count' => (int) ($leaveDays ?? 0),
            'program_statuses' => $statusMap->values()->map(fn (ProgramStatus $status) => [
                'id' => $status->id,
                'name' => $status->name,
                'code' => $status->code,
            ]),
            'programs' => $guru->programs->map(fn($p) => [
                'id' => $p->id,
                'title' => $p->title,
                'date' => $p->program_date->toDateString(),
                'time' => $p->program_time?->format('H:i'),
                'location' => $p->location,
                'markah' => (int) ($p->markah ?? 0),
                'require_absence_reason' => (bool) $p->require_absence_reason,
                'status_id' => $p->pivot->program_status_id,
                'status_code' => $statusMap->get($p->pivot->program_status_id)?->code,
                'status_name' => $statusMap->get($p->pivot->program_status_id)?->name,
                'absence_reason' => $p->pivot->absence_reason,
            ]),
        ]);
    }

    public function leaveNotices(Request $request): JsonResponse
    {
        $user = $request->user();
        $guru = $user->guru;

        $notices = LeaveNotice::where('guru_id', $guru->id)
            ->latest('leave_date')
            ->get();

        return response()->json(
            $notices->map(fn (LeaveNotice $notice) => [
                'id' => $notice->id,
                'leave_date' => $notice->leave_date?->toDateString(),
                'leave_until' => $notice->leave_until?->toDateString(),
                'reason' => $notice->reason,
                'mc_image_url' => $this->assetUrl($notice->mc_image_url),
                'created_at' => $notice->created_at?->toDateTimeString(),
            ])
        );
    }

    public function storeLeaveNotice(Request $request): JsonResponse
    {
        $user = $request->user();
        $guru = $user->guru;

        $data = $request->validate([
            'leave_date' => ['required', 'date'],
            'leave_until' => ['required', 'date', 'after_or_equal:leave_date'],
            'reason' => ['required', 'string'],
            'mc_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $leaveNotice = LeaveNotice::create([
            'guru_id' => $guru->id,
            'leave_date' => $data['leave_date'],
            'leave_until' => $data['leave_until'],
            'reason' => $data['reason'],
        ]);

        if ($request->hasFile('mc_image')) {
            $leaveNotice->update([
                'mc_image_path' => $request->file('mc_image')->store('leave-mc', 'public'),
            ]);
        }

        // Notify admins
        $masterAdmins = User::role('master_admin')->get();
        $relatedAdmins = User::role('admin')
            ->whereHas('assignedPastis', fn ($q) => $q->whereKey($guru->pasti_id))
            ->get();
        $recipients = $masterAdmins->merge($relatedAdmins)->unique('id')->values();

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new LeaveNoticeSubmittedNotification($leaveNotice));
        }

        return response()->json([
            'message' => 'Notis cuti berjaya dihantar.',
            'data' => [
                'id' => $leaveNotice->id,
                'leave_date' => $leaveNotice->leave_date?->toDateString(),
                'leave_until' => $leaveNotice->leave_until?->toDateString(),
                'reason' => $leaveNotice->reason,
                'mc_image_url' => $this->assetUrl($leaveNotice->mc_image_url),
                'created_at' => $leaveNotice->created_at?->toDateTimeString(),
            ],
        ], 201);
    }

    public function updateProgramStatus(Request $request, Program $program): JsonResponse
    {
        $user = $request->user();
        $guru = $user->guru;

        $data = $request->validate([
            'program_status_id' => ['required', 'integer', 'exists:program_statuses,id'],
            'absence_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $selectedStatus = ProgramStatus::findOrFail($data['program_status_id']);
        if (!in_array($selectedStatus->code, ['HADIR', 'TIDAK_HADIR'], true)) {
            return response()->json(['message' => 'Invalid status code'], 422);
        }

        $isAssigned = $program->gurus()->where('gurus.id', $guru->id)->exists();
        if (! $isAssigned) {
            return response()->json(['message' => 'Anda tidak ditugaskan ke program ini.'], 403);
        }

        if ($program->require_absence_reason && $selectedStatus->code === 'TIDAK_HADIR' && blank($data['absence_reason'])) {
            return response()->json(['message' => __('messages.absence_reason_required')], 422);
        }

        $participation = $this->participationService->updateStatus(
            $program->id,
            $guru->id,
            $data['program_status_id'],
            $selectedStatus->code === 'TIDAK_HADIR' ? $data['absence_reason'] : null,
            $user->id
        );

        $this->kpiCalculationService->recalculateForGuru($guru);

        if ($selectedStatus->code === 'TIDAK_HADIR' && filled($participation->absence_reason)) {
            $masterAdmins = User::role('master_admin')->get();
            $relatedAdmins = User::role('admin')
                ->whereHas('assignedPastis', fn ($q) => $q->whereKey($guru->pasti_id))
                ->get();
            $recipients = $masterAdmins->merge($relatedAdmins)->unique('id')->values();

            if ($recipients->isNotEmpty()) {
                Notification::send($recipients, new ProgramAbsenceReasonSubmittedNotification($participation));
            }
        }

        return response()->json([
            'message' => 'Status program berjaya dikemaskini.',
            'participation' => [
                'program_id' => $participation->program_id,
                'guru_id' => $participation->guru_id,
                'program_status_id' => $participation->program_status_id,
                'program_status_name' => $selectedStatus->name,
                'program_status_code' => $selectedStatus->code,
                'absence_reason' => $participation->absence_reason,
                'updated_at' => $participation->updated_at?->toDateTimeString(),
            ],
        ]);
    }

    public function completeProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        $guru = $user->guru;

        if (! $guru) {
            return response()->json(['message' => 'Guru profile not found.'], 404);
        }

        $avatarRules = ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'];
        if (blank($user->avatar_path)) {
            $avatarRules[0] = 'required';
        }

        $data = $request->validate([
            'nama_samaran' => ['required', 'string', 'max:255'],
            'tarikh_lahir' => ['required', 'date'],
            'pasti_id' => ['required', 'integer', 'exists:pastis,id'],
            'phone' => ['required', 'string', 'max:30'],
            'marital_status' => ['required', 'string', 'in:single,married,widowed,divorced'],
            'joined_at' => ['required', 'date'],
            'avatar' => $avatarRules,
        ]);

        $user->update([
            'nama_samaran' => $data['nama_samaran'],
            'tarikh_lahir' => $data['tarikh_lahir'],
        ]);

        $guru->update([
            'pasti_id' => $data['pasti_id'],
            'phone' => $data['phone'],
            'marital_status' => $data['marital_status'],
            'joined_at' => $data['joined_at'],
        ]);

        if ($request->hasFile('avatar')) {
            if ($user->avatar_path) {
                Storage::disk('public')->delete($user->avatar_path);
            }

            $user->update([
                'avatar_path' => $request->file('avatar')->store('avatars', 'public'),
            ]);
        }

        $freshUser = $user->refresh()->load('guru.pasti.kawasan');
        $missingFields = $this->profileCompletionService->missingFields($freshUser);

        return response()->json([
            'message' => 'Profil berjaya dikemaskini.',
            'profile_completed' => $missingFields === [],
            'missing_fields' => $missingFields,
            'missing_profile_fields' => $missingFields,
            'user' => [
                'id' => $freshUser->id,
                'name' => $freshUser->name,
                'nama_samaran' => $freshUser->nama_samaran,
                'email' => $freshUser->email,
                'tarikh_lahir' => $freshUser->tarikh_lahir?->toDateString(),
                'avatar_url' => $this->assetUrl($freshUser->avatar_url),
            ],
            'guru' => [
                'pasti_id' => $freshUser->guru?->pasti_id,
                'pasti_name' => $freshUser->guru?->pasti?->name,
                'kawasan_name' => $freshUser->guru?->pasti?->kawasan?->name,
                'phone' => $freshUser->guru?->phone,
                'marital_status' => $freshUser->guru?->marital_status,
                'joined_at' => $freshUser->guru?->joined_at?->toDateString(),
            ],
        ]);
    }

    public function pastiOptions(): JsonResponse
    {
        $pastis = Pasti::query()
            ->with('kawasan:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'kawasan_id']);

        return response()->json([
            'data' => $pastis->map(fn (Pasti $pasti) => [
                'id' => $pasti->id,
                'name' => $pasti->name,
                'kawasan_name' => $pasti->kawasan?->name,
            ]),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }

    private function assetUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return asset($path);
    }

}
