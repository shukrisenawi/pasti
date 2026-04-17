<?php

namespace App\Http\Controllers;

use App\Models\Pasti;
use App\Models\PastiInformationRequest;
use App\Models\User;
use App\Notifications\PastiInformationRequestedNotification;
use App\Notifications\PastiInformationUpdatedNotification;
use App\Services\N8nWebhookService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class PastiInformationController extends Controller
{
    public function __construct(
        private readonly N8nWebhookService $n8nWebhookService,
    ) {
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->hasAnyRole(['master_admin', 'admin', 'guru']), 403);

        return view('pasti-information.index');
    }

    public function requestAllUpdates(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasAnyRole(['master_admin', 'admin']), 403);

        $pastis = $this->accessiblePastisQueryForUser($user)->get(['pastis.id', 'pastis.name']);
        if ($pastis->isEmpty()) {
            return back();
        }

        $pastiIds = $pastis->pluck('id')->all();
        $hasPendingRequests = PastiInformationRequest::query()
            ->whereIn('pasti_id', $pastiIds)
            ->whereNull('completed_at')
            ->exists();

        if ($hasPendingRequests) {
            return back()->withErrors([
                'pasti_information' => __('messages.pasti_info_wait_until_complete'),
            ]);
        }

        $now = now();
        foreach ($pastis as $pasti) {
            $infoRequest = PastiInformationRequest::query()->create([
                'pasti_id' => $pasti->id,
                'requested_by' => $user->id,
                'requested_at' => $now,
            ]);
            $infoRequest->setRelation('pasti', $pasti);

            $recipients = $this->pastiGuruUsers((int) $pasti->id);
            if ($recipients->isNotEmpty()) {
                Notification::send($recipients, new PastiInformationRequestedNotification($infoRequest));
            }
        }

        $this->n8nWebhookService->sendByTemplate(
            N8nWebhookService::KEY_TEXT_PASTI_INFO_REQUEST,
            ['tarikh' => now()->format('d/m/Y')],
            $this->n8nWebhookService->toActionUrl(route('pasti-information.index'))
        );

        return back()->with('status', __('messages.pasti_info_request_sent'));
    }

    public function edit(Request $request, PastiInformationRequest $pastiInformationRequest): View|RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasRole('guru'), 403);
        $this->ensureGuruCanFill($user, $pastiInformationRequest);

        if ($pastiInformationRequest->completed_at !== null) {
            return redirect()
                ->route('pasti-information.index')
                ->withErrors(['pasti_information' => __('messages.pasti_info_already_completed')]);
        }

        return view('pasti-information.form', [
            'infoRequest' => $pastiInformationRequest->load('pasti'),
        ]);
    }

    public function update(Request $request, PastiInformationRequest $pastiInformationRequest): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasRole('guru'), 403);
        $this->ensureGuruCanFill($user, $pastiInformationRequest);

        $data = $request->validate([
            'jumlah_guru' => ['required', 'integer', 'min:0'],
            'jumlah_pembantu_guru' => ['required', 'integer', 'min:0'],
            'murid_lelaki_4_tahun' => ['required', 'integer', 'min:0'],
            'murid_perempuan_4_tahun' => ['required', 'integer', 'min:0'],
            'murid_lelaki_5_tahun' => ['required', 'integer', 'min:0'],
            'murid_perempuan_5_tahun' => ['required', 'integer', 'min:0'],
            'murid_lelaki_6_tahun' => ['required', 'integer', 'min:0'],
            'murid_perempuan_6_tahun' => ['required', 'integer', 'min:0'],
        ]);

        $affectedRows = PastiInformationRequest::query()
            ->whereKey($pastiInformationRequest->id)
            ->whereNull('completed_at')
            ->update([
                ...$data,
                'completed_by' => $user->id,
                'completed_at' => now(),
            ]);

        if ($affectedRows === 0) {
            return redirect()
                ->route('pasti-information.index')
                ->withErrors(['pasti_information' => __('messages.pasti_info_already_completed')]);
        }

        $pastiInformationRequest->loadMissing(['pasti', 'completedBy']);
        $masterAdmins = User::role('master_admin')->get();
        $relatedAdmins = User::role('admin')
            ->whereHas('assignedPastis', fn ($q) => $q->whereKey($pastiInformationRequest->pasti_id))
            ->get();
        $adminRecipients = $masterAdmins->merge($relatedAdmins)->unique('id')->values();

        if ($adminRecipients->isNotEmpty()) {
            Notification::send($adminRecipients, new PastiInformationUpdatedNotification($pastiInformationRequest));
        }

        $allPastiInfoCompleted = ! PastiInformationRequest::query()
            ->whereNull('completed_at')
            ->exists();

        if ($allPastiInfoCompleted) {
            $this->n8nWebhookService->sendGroup2ByTemplate(
                N8nWebhookService::KEY_TEXT_ALL_PASTI_INFO_COMPLETED,
                ['tarikh' => now()->format('d/m/Y H:i')],
                $this->n8nWebhookService->toActionUrl(route('pasti-information.index'))
            );
        }

        return redirect()->route('pasti-information.index')->with('status', __('messages.saved'));
    }

    private function accessiblePastisQueryForUser(User $user): Builder
    {
        $query = Pasti::query();

        if ($user->hasRole('guru')) {
            $query->whereKey($user->guru?->pasti_id ?: 0);
        } elseif ($user->hasRole('admin')) {
            $query->whereIn('id', $this->assignedPastiIds($user));
        }

        return $query;
    }

    private function ensureGuruCanFill(User $user, PastiInformationRequest $infoRequest): void
    {
        $guruPastiId = $user->guru?->pasti_id;

        abort_unless($guruPastiId && (int) $guruPastiId === (int) $infoRequest->pasti_id, 403);

        $latestRequestId = PastiInformationRequest::query()
            ->where('pasti_id', $infoRequest->pasti_id)
            ->latest('id')
            ->value('id');

        abort_unless($latestRequestId && (int) $latestRequestId === (int) $infoRequest->id, 403);
    }

    private function pastiGuruUsers(int $pastiId)
    {
        return User::query()
            ->select('users.*')
            ->role('guru')
            ->whereHas('guru', fn ($q) => $q
                ->where('pasti_id', $pastiId)
                ->where('active', true)
            )
            ->get();
    }
}
