<?php

namespace App\Http\Controllers;

use App\Models\FinancialTransaction;
use App\Models\FinancialTransactionType;
use App\Models\Pasti;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FinancialController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->hasAnyRole(['master_admin', 'admin']), 403);

        $allowedTabs = $user->hasRole('master_admin')
            ? ['ringkasan', 'transaksi', 'jenis-transaksi']
            : ['ringkasan', 'transaksi'];
        $activeTab = in_array($request->query('tab'), $allowedTabs, true)
            ? $request->query('tab')
            : 'ringkasan';

        $pastis = Pasti::query()
            ->with('kawasan')
            ->when(
                $user->hasRole('admin') && ! $user->hasRole('master_admin'),
                fn ($query) => $query->whereIn('id', $this->assignedPastiIds($user))
            )
            ->orderBy('name')
            ->get();

        $accessiblePastiIds = $pastis->pluck('id')->all();

        $transactions = FinancialTransaction::query()
            ->with(['pasti.kawasan', 'creator', 'transactionType'])
            ->when(
                $user->hasRole('admin') && ! $user->hasRole('master_admin'),
                fn ($query) => $query->where(function ($q) use ($accessiblePastiIds): void {
                    $q->whereNull('pasti_id');

                    if ($accessiblePastiIds !== []) {
                        $q->orWhereIn('pasti_id', $accessiblePastiIds);
                    }
                })
            )
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        $balanceExpression = "SUM(CASE WHEN COALESCE(credit_debit, CASE WHEN transaction_type = 'masuk' THEN 'credit' ELSE 'debit' END) = 'credit' THEN amount ELSE -amount END)";

        $currentBalance = (float) (FinancialTransaction::query()
            ->when(
                $user->hasRole('admin') && ! $user->hasRole('master_admin'),
                fn ($query) => $query->where(function ($q) use ($accessiblePastiIds): void {
                    $q->whereNull('pasti_id');

                    if ($accessiblePastiIds !== []) {
                        $q->orWhereIn('pasti_id', $accessiblePastiIds);
                    }
                })
            )
            ->selectRaw($balanceExpression . ' as balance')
            ->value('balance') ?? 0);

        $cashBalance = (float) (FinancialTransaction::query()
            ->when(
                $user->hasRole('admin') && ! $user->hasRole('master_admin'),
                fn ($query) => $query->where(function ($q) use ($accessiblePastiIds): void {
                    $q->whereNull('pasti_id');

                    if ($accessiblePastiIds !== []) {
                        $q->orWhereIn('pasti_id', $accessiblePastiIds);
                    }
                })
            )
            ->where('payment_method', 'cash')
            ->selectRaw($balanceExpression . ' as balance')
            ->value('balance') ?? 0);

        $bankBalance = (float) (FinancialTransaction::query()
            ->when(
                $user->hasRole('admin') && ! $user->hasRole('master_admin'),
                fn ($query) => $query->where(function ($q) use ($accessiblePastiIds): void {
                    $q->whereNull('pasti_id');

                    if ($accessiblePastiIds !== []) {
                        $q->orWhereIn('pasti_id', $accessiblePastiIds);
                    }
                })
            )
            ->where(function ($query): void {
                $query->where('payment_method', 'transfer')
                    ->orWhereNull('payment_method');
            })
            ->selectRaw($balanceExpression . ' as balance')
            ->value('balance') ?? 0);

        return view('financial.index', [
            'activeTab' => $activeTab,
            'pastis' => $pastis,
            'transactions' => $transactions,
            'currentBalance' => $currentBalance,
            'cashBalance' => $cashBalance,
            'bankBalance' => $bankBalance,
            'transactionTypes' => FinancialTransactionType::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'allTransactionTypes' => FinancialTransactionType::query()
                ->orderBy('name')
                ->get(),
            'editingTransactionType' => $this->editingTransactionType($request),
            'canManageTypes' => $user->hasRole('master_admin'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasAnyRole(['master_admin', 'admin']), 403);

        $allowedPastiIds = Pasti::query()
            ->when(
                $user->hasRole('admin') && ! $user->hasRole('master_admin'),
                fn ($query) => $query->whereIn('id', $this->assignedPastiIds($user))
            )
            ->pluck('id')
            ->all();

        $data = $request->validate([
            'pasti_id' => ['nullable', 'integer', 'in:' . implode(',', $allowedPastiIds ?: [0])],
            'financial_transaction_type_id' => [
                'required',
                'integer',
                Rule::exists('financial_transaction_types', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'transaction_date' => ['required', 'date'],
            'credit_debit' => ['required', 'in:credit,debit'],
            'payment_method' => ['required', 'in:cash,transfer'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'transaction_remark' => ['nullable', 'string', 'max:1000'],
        ]);

        FinancialTransaction::query()->create([
            'pasti_id' => $data['pasti_id'] ? (int) $data['pasti_id'] : null,
            'financial_transaction_type_id' => (int) $data['financial_transaction_type_id'],
            'transaction_date' => $data['transaction_date'],
            'credit_debit' => $data['credit_debit'],
            'payment_method' => $data['payment_method'],
            'amount' => $data['amount'],
            'transaction_remark' => $data['transaction_remark'] ?? null,
            'created_by' => $user->id,
        ]);

        return redirect()
            ->route('financial.index', ['tab' => 'transaksi'])
            ->with('status', __('messages.saved'));
    }

    public function storeType(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasRole('master_admin'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('financial_transaction_types', 'name')],
        ]);

        FinancialTransactionType::query()->create([
            'name' => $data['name'],
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        return redirect()
            ->route('financial.index', ['tab' => 'jenis-transaksi'])
            ->with('status', __('messages.saved'));
    }

    public function updateType(Request $request, FinancialTransactionType $financialTransactionType): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasRole('master_admin'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('financial_transaction_types', 'name')->ignore($financialTransactionType->id)],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $financialTransactionType->update([
            'name' => $data['name'],
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);

        return redirect()
            ->route('financial.index', ['tab' => 'jenis-transaksi'])
            ->with('status', __('messages.saved'));
    }

    public function destroyType(Request $request, FinancialTransactionType $financialTransactionType): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasRole('master_admin'), 403);

        if ($financialTransactionType->transactions()->exists()) {
            return redirect()
                ->route('financial.index', ['tab' => 'jenis-transaksi'])
                ->withErrors(['financial_transaction_type' => __('messages.financial_type_in_use')]);
        }

        $financialTransactionType->delete();

        return redirect()
            ->route('financial.index', ['tab' => 'jenis-transaksi'])
            ->with('status', __('messages.deleted'));
    }

    private function editingTransactionType(Request $request): ?FinancialTransactionType
    {
        if (! $request->user()->hasRole('master_admin')) {
            return null;
        }

        $editId = (int) $request->integer('edit_type');
        if ($editId <= 0) {
            return null;
        }

        return FinancialTransactionType::query()->find($editId);
    }
}
