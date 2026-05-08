<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\ShirtPurchase;
use App\Models\ShirtPurchaseResponse;
use App\Services\N8nWebhookService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ShirtPurchaseController extends Controller
{
    public function __construct(
        private readonly N8nWebhookService $n8nWebhookService,
    ) {
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->hasAnyRole(['master_admin', 'admin', 'guru']), 403);

        if ($user->isOperatingAsGuru()) {
            $responses = ShirtPurchaseResponse::query()
                ->with(['purchase'])
                ->where('guru_id', $user->operatingGuruProfile()?->id ?? 0)
                ->orderByDesc(
                    ShirtPurchase::query()
                        ->select('id')
                        ->whereColumn('shirt_purchases.id', 'shirt_purchase_responses.shirt_purchase_id')
                        ->limit(1)
                )
                ->latest('id')
                ->get();

            return view('shirt-purchases.guru-index', [
                'responses' => $responses,
                'sizeOptions' => ShirtPurchase::SIZE_OPTIONS,
            ]);
        }

        $purchases = ShirtPurchase::query()
            ->withCount('responses')
            ->withCount([
                'responses as submitted_count' => fn (Builder $query) => $query->whereNotNull('size'),
                'responses as payment_notice_count' => fn (Builder $query) => $query->whereNotNull('paid_at'),
                'responses as approved_count' => fn (Builder $query) => $query->whereNotNull('approved_at'),
            ])
            ->when(
                $user->hasRole('admin') && ! $user->hasRole('master_admin'),
                fn (Builder $query) => $query->where('created_by', $user->id)
            )
            ->latest('id')
            ->paginate(10);

        return view('shirt-purchases.index', [
            'isGuru' => false,
            'isAdmin' => true,
            'purchases' => $purchases,
            'sizeOptions' => ShirtPurchase::SIZE_OPTIONS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isOperatingAsAdmin(), 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:7168'],
        ]);

        $gurus = $this->recipientGurus($user)->get();
        if ($gurus->isEmpty()) {
            return back()->withErrors([
                'title' => 'Tiada guru aktif dijumpai untuk pembelian ini.',
            ])->withInput();
        }

        $purchaseImagePath = $request->hasFile('image')
            ? $request->file('image')->store('shirt-purchases', 'public')
            : null;

        $purchase = DB::transaction(function () use ($user, $data, $gurus, $purchaseImagePath): ShirtPurchase {
            $purchase = ShirtPurchase::query()->create([
                'title' => trim((string) $data['title']),
                'description' => trim((string) ($data['description'] ?? '')) ?: null,
                'image_path' => $purchaseImagePath,
                'created_by' => $user->id,
                'sent_to_n8n_at' => now(),
            ]);

            $rows = $gurus->map(fn (Guru $guru): array => [
                'shirt_purchase_id' => $purchase->id,
                'guru_id' => $guru->id,
                'quantity' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ])->all();

            ShirtPurchaseResponse::query()->insert($rows);

            return $purchase;
        });
        try {
            $this->n8nWebhookService->sendByTemplate(
                N8nWebhookService::KEY_TEXT_SHIRT_PURCHASE_REQUEST,
                [
                    'tajuk' => $purchase->title,
                    'keterangan' => $purchase->description ?? '-',
                ],
                $this->n8nWebhookService->toActionUrl(route('shirt-purchases.index')),
                $purchase->image_path ? $this->n8nWebhookService->toPublicUrl($purchase->image_url) : null
            );
        } catch (\Throwable $exception) {
            if ($purchaseImagePath) {
                Storage::disk('public')->delete($purchaseImagePath);
            }

            throw $exception;
        }

        return redirect()->route('shirt-purchases.index')->with('status', __('messages.saved'));
    }

    public function show(Request $request, ShirtPurchase $shirtPurchase): View
    {
        $user = $request->user();

        if ($user->isOperatingAsGuru()) {
            $response = ShirtPurchaseResponse::query()
                ->with(['purchase', 'guru.pasti'])
                ->where('shirt_purchase_id', $shirtPurchase->id)
                ->where('guru_id', $user->operatingGuruProfile()?->id ?? 0)
                ->firstOrFail();

            return view('shirt-purchases.guru-show', [
                'purchase' => $shirtPurchase,
                'response' => $response,
                'sizeOptions' => ShirtPurchase::SIZE_OPTIONS,
            ]);
        }

        abort_unless($user->isOperatingAsAdmin(), 403);
        $this->ensureAdminCanAccessPurchase($user, $shirtPurchase);

        $shirtPurchase->load([
            'creator',
            'responses.guru.pasti',
            'responses.paidMarker',
            'responses.approver',
        ]);

        return view('shirt-purchases.show', [
            'purchase' => $shirtPurchase,
            'submittedResponses' => $shirtPurchase->responses
                ->filter(fn (ShirtPurchaseResponse $response): bool => $response->submitted_at !== null && filled($response->size))
                ->sortBy(fn (ShirtPurchaseResponse $response) => $response->guru?->display_name)
                ->values(),
        ]);
    }

    public function updateResponse(Request $request, ShirtPurchaseResponse $response): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isOperatingAsGuru(), 403);
        abort_unless((int) ($user->operatingGuruProfile()?->id ?? 0) === (int) $response->guru_id, 403);

        $data = $request->validate([
            'size' => ['required', 'in:' . implode(',', ShirtPurchase::SIZE_OPTIONS)],
            'notes' => ['nullable', 'string', 'max:1000'],
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'is_paid' => ['nullable', 'boolean'],
        ]);

        $isPaid = $request->boolean('is_paid');

        DB::transaction(function () use ($response, $data, $isPaid): void {
            $response->update([
                'size' => $data['size'],
                'notes' => trim((string) ($data['notes'] ?? '')) ?: null,
                'quantity' => (int) $data['quantity'],
                'submitted_at' => now(),
                'paid_at' => $isPaid ? ($response->paid_at ?? now()) : null,
                'paid_marked_by' => $isPaid ? $response->paid_marked_by : null,
                'approved_at' => $isPaid ? $response->approved_at : null,
                'approved_by' => $isPaid ? $response->approved_by : null,
            ]);

            $response->guru()->update([
                'default_baju_size' => $data['size'],
            ]);
        });

        return redirect()
            ->route('shirt-purchases.index')
            ->with('status', __('messages.saved'))
            ->with('shirt_purchase_success_message', 'Pembelian baju berjaya dihantar.')
            ->with('shirt_purchase_success_actor', 'guru');
    }

    public function markPaid(Request $request, ShirtPurchaseResponse $response): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        abort_unless($user->isOperatingAsAdmin(), 403);
        $this->ensureAdminCanAccessResponse($user, $response);

        $response->update([
            'paid_at' => $response->paid_at ?? now(),
            'paid_marked_by' => $user->id,
            'approved_at' => $response->approved_at ?? now(),
            'approved_by' => $response->approved_by ?? $user->id,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('messages.saved'),
                'response' => [
                    'id' => $response->id,
                    'payment_notice' => $response->paid_at !== null,
                    'paid' => $response->approved_at !== null,
                    'approved' => $response->approved_at !== null,
                ],
            ]);
        }

        return back()->with('status', __('messages.saved'));
    }

    public function approve(Request $request, ShirtPurchaseResponse $response): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isOperatingAsAdmin(), 403);
        $this->ensureAdminCanAccessResponse($user, $response);

        if ($response->paid_at === null) {
            return back()->withErrors([
                'shirt_purchase' => 'Guru perlu ditandakan sudah bayar sebelum approve.',
            ]);
        }

        $response->update([
            'approved_at' => now(),
            'approved_by' => $user->id,
        ]);

        return back()->with('status', __('messages.saved'));
    }

    public function broadcast(Request $request, ShirtPurchase $shirtPurchase): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isOperatingAsAdmin(), 403);
        $this->ensureAdminCanAccessPurchase($user, $shirtPurchase);

        $shirtPurchase->loadMissing('responses.guru');

        $senarai = $shirtPurchase->responses
            ->filter(fn (ShirtPurchaseResponse $response): bool => filled($response->size))
            ->values()
            ->map(function (ShirtPurchaseResponse $response, int $index): string {
                $line = ($index + 1) . '. '
                    . ($response->guru?->display_name ?? '-')
                    . ' - ' . ($response->size ?? '-');

                if ((int) $response->quantity > 1) {
                    $line .= ' (' . (int) $response->quantity . ' helai)';
                }

                if ($response->approved_at) {
                    $line .= ' ✓';
                }

                return $line;
            })
            ->implode("\n");

        if ($senarai === '') {
            return back()->withErrors([
                'shirt_purchase' => 'Belum ada guru yang isi saiz untuk dikeluarkan.',
            ]);
        }

        $this->n8nWebhookService->sendByTemplate(
            N8nWebhookService::KEY_TEXT_SHIRT_PURCHASE_LIST,
            [
                'tajuk' => $shirtPurchase->title,
                'senarai' => $senarai,
            ],
            $this->n8nWebhookService->toActionUrl(route('shirt-purchases.show', $shirtPurchase))
        );

        $shirtPurchase->update([
            'last_broadcast_at' => now(),
        ]);

        return back()->with('status', 'Senarai pembelian berjaya dihantar ke group guru.');
    }

    private function recipientGurus($user): Builder
    {
        return Guru::query()
            ->where('active', true)
            ->where('is_assistant', false)
            ->whereNotNull('user_id')
            ->when(
                $user->hasRole('admin') && ! $user->hasRole('master_admin'),
                fn (Builder $query) => $query->whereIn('pasti_id', $this->assignedPastiIds($user))
            )
            ->with(['user', 'pasti'])
            ->orderBy('name');
    }

    private function ensureAdminCanAccessPurchase($user, ShirtPurchase $purchase): void
    {
        if ($user->hasRole('master_admin')) {
            return;
        }

        abort_unless((int) $purchase->created_by === (int) $user->id, 403);
    }

    private function ensureAdminCanAccessResponse($user, ShirtPurchaseResponse $response): void
    {
        $response->loadMissing('purchase');
        $this->ensureAdminCanAccessPurchase($user, $response->purchase);
    }
}
