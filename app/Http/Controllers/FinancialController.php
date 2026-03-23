<?php

namespace App\Http\Controllers;

use App\Models\FinancialTransaction;
use App\Models\Pasti;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FinancialController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->hasAnyRole(['master_admin', 'admin']), 403);

        $activeTab = in_array($request->query('tab'), ['ringkasan', 'transaksi'], true)
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
            ->with(['pasti.kawasan', 'creator'])
            ->when(
                $accessiblePastiIds !== [],
                fn ($query) => $query->whereIn('pasti_id', $accessiblePastiIds),
                fn ($query) => $query->whereRaw('1 = 0')
            )
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        $balanceExpression = "SUM(CASE WHEN transaction_type = 'masuk' THEN amount ELSE -amount END)";

        $currentBalance = (float) (FinancialTransaction::query()
            ->when(
                $accessiblePastiIds !== [],
                fn ($query) => $query->whereIn('pasti_id', $accessiblePastiIds),
                fn ($query) => $query->whereRaw('1 = 0')
            )
            ->selectRaw($balanceExpression . ' as balance')
            ->value('balance') ?? 0);

        $debtorBalances = FinancialTransaction::query()
            ->select('pasti_id')
            ->selectRaw($balanceExpression . ' as balance')
            ->when(
                $accessiblePastiIds !== [],
                fn ($query) => $query->whereIn('pasti_id', $accessiblePastiIds),
                fn ($query) => $query->whereRaw('1 = 0')
            )
            ->groupBy('pasti_id')
            ->havingRaw($balanceExpression . ' < 0')
            ->get();

        $debtorPastis = $debtorBalances
            ->map(function ($item) use ($pastis) {
                $pasti = $pastis->firstWhere('id', (int) $item->pasti_id);
                if (! $pasti) {
                    return null;
                }

                return (object) [
                    'pasti_name' => $pasti->name,
                    'kawasan_name' => $pasti->kawasan?->name ?? '-',
                    'balance' => (float) $item->balance,
                ];
            })
            ->filter()
            ->sortBy('kawasan_name')
            ->values();

        $debtByKawasan = $debtorPastis
            ->groupBy('kawasan_name')
            ->map(function ($rows, $kawasanName) {
                $totalDebt = collect($rows)->sum(fn ($row) => abs((float) $row->balance));

                return (object) [
                    'kawasan_name' => $kawasanName,
                    'total_debt' => $totalDebt,
                ];
            })
            ->sortBy('kawasan_name')
            ->values();

        return view('financial.index', [
            'activeTab' => $activeTab,
            'pastis' => $pastis,
            'transactions' => $transactions,
            'currentBalance' => $currentBalance,
            'debtorPastis' => $debtorPastis,
            'debtByKawasan' => $debtByKawasan,
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
            'pasti_id' => ['required', 'integer', 'in:' . implode(',', $allowedPastiIds ?: [0])],
            'transaction_date' => ['required', 'date'],
            'transaction_type' => ['required', 'in:masuk,keluar'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'amount_remark' => ['nullable', 'string', 'max:255'],
            'transaction_remark' => ['nullable', 'string', 'max:1000'],
        ]);

        FinancialTransaction::query()->create([
            'pasti_id' => (int) $data['pasti_id'],
            'transaction_date' => $data['transaction_date'],
            'transaction_type' => $data['transaction_type'],
            'amount' => $data['amount'],
            'amount_remark' => $data['amount_remark'] ?? null,
            'transaction_remark' => $data['transaction_remark'] ?? null,
            'created_by' => $user->id,
        ]);

        return redirect()
            ->route('financial.index', ['tab' => 'transaksi'])
            ->with('status', __('messages.saved'));
    }
}
