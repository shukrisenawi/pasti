<?php

namespace App\Http\Controllers;

use App\Models\Claim;
use App\Models\FinancialTransaction;
use App\Models\FinancialTransactionType;
use App\Models\User;
use App\Notifications\ClaimApprovedNotification;
use App\Notifications\ClaimSubmittedNotification;
use App\Services\N8nWebhookService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class ClaimController extends Controller
{
    public function __construct(
        private readonly N8nWebhookService $n8nWebhookService,
    ) {
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->hasAnyRole(['master_admin', 'admin', 'guru']), 403);

        $allowedTabs = ['list'];
        if ($user->hasRole('guru')) {
            $allowedTabs[] = 'submit';
        }
        if ($user->hasAnyRole(['master_admin', 'admin'])) {
            $allowedTabs[] = 'pending';
        }
        $activeTab = in_array($request->query('tab'), $allowedTabs, true)
            ? $request->query('tab')
            : 'list';

        $claimsQuery = Claim::query()
            ->with(['user', 'pasti', 'approver'])
            ->latest('claim_date')
            ->latest('id');

        if ($this->isGuruOnly($user)) {
            $claimsQuery->where('user_id', $user->id);
        } elseif ($user->hasRole('admin') && ! $user->hasRole('master_admin')) {
            $assignedPastiIds = $this->assignedPastiIds($user);
            $claimsQuery->where(function ($query) use ($assignedPastiIds, $user): void {
                $query->where('user_id', $user->id);

                if ($assignedPastiIds !== []) {
                    $query->orWhereIn('pasti_id', $assignedPastiIds);
                }
            });
        }

        $pendingClaimsQuery = Claim::query()
            ->with(['user', 'pasti'])
            ->where('status', 'pending')
            ->latest('claim_date')
            ->latest('id');

        if ($user->hasRole('admin') && ! $user->hasRole('master_admin')) {
            $assignedPastiIds = $this->assignedPastiIds($user);
            $pendingClaimsQuery->whereIn('pasti_id', $assignedPastiIds === [] ? [0] : $assignedPastiIds);
        } elseif ($this->isGuruOnly($user)) {
            $pendingClaimsQuery->where('id', 0);
        }

        return view('claims.index', [
            'activeTab' => $activeTab,
            'claims' => $claimsQuery->paginate(9)->withQueryString(),
            'pendingClaims' => $pendingClaimsQuery->paginate(9, ['*'], 'pending_page')->withQueryString(),
            'canApprove' => $user->hasAnyRole(['master_admin', 'admin']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasRole('guru'), 403);

        $data = $request->validate([
            'notes' => ['required', 'string', 'max:1000'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'claim_date' => ['required', 'date'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $guru = $user->guru;

        $claim = Claim::query()->create([
            'user_id' => $user->id,
            'guru_id' => $guru?->id,
            'pasti_id' => $guru?->pasti_id,
            'claim_date' => $data['claim_date'],
            'amount' => $data['amount'],
            'notes' => $data['notes'],
            'status' => 'pending',
        ]);

        if ($request->hasFile('image')) {
            $claim->update([
                'image_path' => $request->file('image')->store('claims', 'public'),
            ]);
        }

        $claim->loadMissing(['user', 'pasti']);
        $recipients = User::role('master_admin')->get();
        if ($claim->pasti_id) {
            $relatedAdmins = User::role('admin')
                ->whereHas('assignedPastis', fn ($query) => $query->whereKey($claim->pasti_id))
                ->get();
            $recipients = $recipients->merge($relatedAdmins);
        }
        $recipients = $recipients->unique('id')->values();

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new ClaimSubmittedNotification($claim));
        }

        $this->n8nWebhookService->sendGroup2ByTemplate(
            N8nWebhookService::KEY_TEXT_CLAIM_SUBMITTED,
            [
                'nama_guru' => (string) ($claim->user?->display_name ?? $user->display_name),
                'jumlah' => number_format((float) $claim->amount, 2, '.', ''),
                'tarikh_claim' => optional($claim->claim_date)->format('d/m/Y') ?? (string) $data['claim_date'],
                'catatan' => trim((string) $claim->notes),
            ],
            $this->n8nWebhookService->toActionUrl(route('claims.index')),
            $this->n8nWebhookService->toPublicUrl(
                $claim->image_path ? '/uploads/' . ltrim((string) $claim->image_path, '/') : null
            )
        );

        return redirect()
            ->route('claims.index', ['tab' => 'list'])
            ->with('status', __('messages.saved'));
    }

    public function approve(Request $request, Claim $claim): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasAnyRole(['master_admin', 'admin']), 403);

        if ($claim->status !== 'pending') {
            return redirect()
                ->route('claims.index', ['tab' => 'pending'])
                ->withErrors(['claim' => 'Claim ini sudah diproses.']);
        }

        if ((int) $claim->user_id === (int) $user->id) {
            return redirect()
                ->route('claims.index', ['tab' => 'pending'])
                ->withErrors(['claim' => 'Anda tidak boleh meluluskan claim sendiri.']);
        }

        if ($user->hasRole('admin') && ! $user->hasRole('master_admin')) {
            $assignedPastiIds = $this->assignedPastiIds($user);
            if (! $claim->pasti_id || ! in_array((int) $claim->pasti_id, $assignedPastiIds, true)) {
                return redirect()
                    ->route('claims.index', ['tab' => 'pending'])
                    ->withErrors(['claim' => 'Anda tiada akses untuk meluluskan claim ini.']);
            }
        }

        $data = $request->validate([
            'payment_method' => ['required', 'in:cash,transfer'],
            'approved_amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        DB::transaction(function () use ($claim, $data, $user): void {
            $claim->update([
                'status' => 'approved',
                'payment_method' => $data['payment_method'],
                'approved_amount' => $data['approved_amount'],
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);

            $claimType = FinancialTransactionType::query()->firstOrCreate(
                ['name' => 'Claim'],
                ['is_active' => true, 'created_by' => $user->id]
            );

            FinancialTransaction::query()->create([
                'pasti_id' => $claim->pasti_id,
                'financial_transaction_type_id' => $claimType->id,
                'transaction_date' => now()->toDateString(),
                'credit_debit' => 'debit',
                'payment_method' => $data['payment_method'],
                'amount' => $data['approved_amount'],
                'transaction_remark' => 'Claim #' . $claim->id . ' - ' . $claim->notes,
                'created_by' => $user->id,
            ]);
        });

        $claim->refresh()->loadMissing(['user', 'pasti']);
        if ($claim->user) {
            $claim->user->notify(new ClaimApprovedNotification($claim));
        }

        return redirect()
            ->route('claims.index', ['tab' => 'pending'])
            ->with('status', __('messages.saved'));
    }

    public function destroy(Request $request, Claim $claim): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasAnyRole(['master_admin', 'admin', 'guru']), 403);

        if ($claim->status !== 'pending') {
            return back()->withErrors(['claim' => 'Hanya claim berstatus pending boleh dipadam.']);
        }

        $canDelete = false;

        if ($user->hasRole('master_admin')) {
            $canDelete = true;
        } elseif ($user->hasRole('admin')) {
            $assignedPastiIds = $this->assignedPastiIds($user);
            if ($claim->pasti_id && in_array((int) $claim->pasti_id, $assignedPastiIds, true)) {
                $canDelete = true;
            } elseif ((int) $claim->user_id === (int) $user->id) {
                $canDelete = true;
            }
        } elseif ($user->hasRole('guru')) {
            if ((int) $claim->user_id === (int) $user->id) {
                $canDelete = true;
            }
        }

        if (! $canDelete) {
            abort(403, 'Anda tiada akses untuk memadam claim ini.');
        }

        $claim->delete();

        return back()->with('status', __('messages.deleted'));
    }

    private function isGuruOnly(User $user): bool
    {
        return $user->hasRole('guru') && ! $user->hasAnyRole(['master_admin', 'admin']);
    }
}
