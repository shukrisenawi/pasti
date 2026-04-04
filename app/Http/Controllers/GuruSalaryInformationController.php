<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\GuruSalaryRequest;
use App\Models\User;
use App\Notifications\GuruSalaryRequestedNotification;
use App\Notifications\GuruSalaryUpdatedNotification;
use App\Services\N8nWebhookService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class GuruSalaryInformationController extends Controller
{
    public function __construct(
        private readonly N8nWebhookService $n8nWebhookService,
    ) {
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->hasAnyRole(['master_admin', 'admin', 'guru']), 403);

        return view('guru-salary-information.index');
    }

    public function requestAllUpdates(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasAnyRole(['master_admin', 'admin']), 403);

        $gurus = $this->accessibleGurusQueryForUser($user)->get(['gurus.id', 'gurus.user_id']);
        if ($gurus->isEmpty()) {
            return back();
        }

        $guruIds = $gurus->pluck('id')->all();
        $hasPendingRequests = GuruSalaryRequest::query()
            ->whereIn('guru_id', $guruIds)
            ->whereNull('completed_at')
            ->exists();

        if ($hasPendingRequests) {
            return back()->withErrors([
                'guru_salary_information' => __('messages.guru_salary_info_wait_until_complete'),
            ]);
        }

        $now = now();
        foreach ($gurus as $guru) {
            $salaryRequest = GuruSalaryRequest::query()->create([
                'guru_id' => $guru->id,
                'requested_by' => $user->id,
                'requested_at' => $now,
            ]);
            $salaryRequest->setRelation('guru', $guru);

            $recipient = $guru->user_id ? User::query()->find($guru->user_id) : null;
            if ($recipient) {
                Notification::send($recipient, new GuruSalaryRequestedNotification($salaryRequest));
            }
        }

        $this->n8nWebhookService->send(
            'Permintaan kemaskini gaji guru telah dihantar. Sila kemaskini gaji dan elaun semasa.',
            $this->n8nWebhookService->toPublicUrl(route('guru-salary-information.index')),
            null
        );

        return back()->with('status', __('messages.guru_salary_info_request_sent'));
    }

    public function edit(Request $request, GuruSalaryRequest $guruSalaryRequest): View|RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasRole('guru'), 403);
        $this->ensureGuruCanFill($user, $guruSalaryRequest);

        if ($guruSalaryRequest->completed_at !== null) {
            return redirect()
                ->route('guru-salary-information.index')
                ->withErrors(['guru_salary_information' => __('messages.guru_salary_info_already_completed')]);
        }

        return view('guru-salary-information.form', [
            'salaryRequest' => $guruSalaryRequest->load('guru.pasti'),
        ]);
    }

    public function update(Request $request, GuruSalaryRequest $guruSalaryRequest): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasRole('guru'), 403);
        $this->ensureGuruCanFill($user, $guruSalaryRequest);

        $data = $request->validate([
            'gaji' => ['required', 'numeric', 'min:0'],
            'elaun' => ['required', 'numeric', 'min:0'],
        ]);

        $affectedRows = GuruSalaryRequest::query()
            ->whereKey($guruSalaryRequest->id)
            ->whereNull('completed_at')
            ->update([
                ...$data,
                'completed_by' => $user->id,
                'completed_at' => now(),
            ]);

        if ($affectedRows === 0) {
            return redirect()
                ->route('guru-salary-information.index')
                ->withErrors(['guru_salary_information' => __('messages.guru_salary_info_already_completed')]);
        }

        $guruSalaryRequest->loadMissing(['guru.pasti', 'completedBy']);

        $masterAdmins = User::role('master_admin')->get();
        $relatedAdmins = User::role('admin')
            ->whereHas('assignedPastis', fn ($query) => $query->whereKey($guruSalaryRequest->guru?->pasti_id))
            ->get();
        $adminRecipients = $masterAdmins->merge($relatedAdmins)->unique('id')->values();

        if ($adminRecipients->isNotEmpty()) {
            Notification::send($adminRecipients, new GuruSalaryUpdatedNotification($guruSalaryRequest));
        }

        return redirect()->route('guru-salary-information.index')->with('status', __('messages.saved'));
    }

    private function accessibleGurusQueryForUser(User $user): Builder
    {
        $query = Guru::query()
            ->where('is_assistant', false)
            ->where('active', true)
            ->whereNotNull('user_id');

        if ($user->hasRole('guru')) {
            $query->whereKey($user->guru?->id ?: 0);
        } elseif ($user->hasRole('admin') && ! $user->hasRole('master_admin')) {
            $query->whereIn('pasti_id', $this->assignedPastiIds($user));
        }

        return $query;
    }

    private function ensureGuruCanFill(User $user, GuruSalaryRequest $salaryRequest): void
    {
        $guruId = $user->guru?->id;
        abort_unless($guruId && (int) $guruId === (int) $salaryRequest->guru_id, 403);

        $latestRequestId = GuruSalaryRequest::query()
            ->where('guru_id', $salaryRequest->guru_id)
            ->latest('id')
            ->value('id');

        abort_unless($latestRequestId && (int) $latestRequestId === (int) $salaryRequest->id, 403);
    }
}
