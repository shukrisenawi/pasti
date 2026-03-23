<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-bold">{{ __('messages.kewangan') }}</h2>
    </x-slot>

    <div class="mb-4 flex flex-wrap gap-2">
        <a href="{{ route('financial.index', ['tab' => 'ringkasan']) }}" class="btn {{ $activeTab === 'ringkasan' ? 'btn-primary' : 'btn-outline' }}">
            {{ __('messages.financial_overview') }}
        </a>
        <a href="{{ route('financial.index', ['tab' => 'transaksi']) }}" class="btn {{ $activeTab === 'transaksi' ? 'btn-primary' : 'btn-outline' }}">
            {{ __('messages.financial_transactions') }}
        </a>
        @if($canManageTypes)
            <a href="{{ route('financial.index', ['tab' => 'jenis-transaksi']) }}" class="btn {{ $activeTab === 'jenis-transaksi' ? 'btn-primary' : 'btn-outline' }}">
                {{ __('messages.financial_type') }}
            </a>
        @endif
    </div>

    @if($activeTab === 'ringkasan')
        <section class="card">
            <p class="text-sm font-semibold text-slate-600">{{ __('messages.current_balance') }}</p>
            <p class="mt-2 text-3xl font-black {{ $currentBalance < 0 ? 'text-rose-600' : 'text-emerald-700' }}">
                RM {{ number_format($currentBalance, 2) }}
            </p>
        </section>
    @elseif($activeTab === 'jenis-transaksi' && $canManageTypes)
        <section class="card">
            <h3 class="text-base font-bold text-slate-900">{{ __('messages.financial_type') }}</h3>
            <form method="POST" action="{{ $editingTransactionType ? route('financial.types.update', $editingTransactionType) : route('financial.types.store') }}" class="mt-4 flex flex-wrap gap-2">
                @csrf
                @if($editingTransactionType)
                    @method('PUT')
                @endif
                <input
                    class="input-base max-w-md"
                    name="name"
                    placeholder="{{ __('messages.financial_type_name') }}"
                    value="{{ old('name', $editingTransactionType?->name) }}"
                    required
                >
                @if($editingTransactionType)
                    <label class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 text-sm">
                        <input type="checkbox" name="is_active" value="1" class="checkbox checkbox-sm" @checked((bool) old('is_active', $editingTransactionType->is_active))>
                        <span>{{ __('messages.active') }}</span>
                    </label>
                @endif
                <button class="btn btn-primary">{{ $editingTransactionType ? __('messages.save') : __('messages.add') }}</button>
                @if($editingTransactionType)
                    <a href="{{ route('financial.index', ['tab' => 'jenis-transaksi']) }}" class="btn btn-outline">{{ __('messages.cancel') }}</a>
                @endif
            </form>
            @error('name')
                <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
            @enderror
            @error('financial_transaction_type')
                <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
            @enderror

            <div class="mt-6 table-wrap">
                <table class="table-base">
                    <thead>
                    <tr>
                        <th>{{ __('messages.financial_type_name') }}</th>
                        <th>{{ __('messages.status') }}</th>
                        <th>{{ __('messages.actions') }}</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                    @forelse($allTransactionTypes as $type)
                        <tr>
                            <td>{{ $type->name }}</td>
                            <td>{{ $type->is_active ? __('messages.active') : __('messages.inactive') }}</td>
                            <td class="flex items-center gap-2">
                                <a href="{{ route('financial.index', ['tab' => 'jenis-transaksi', 'edit_type' => $type->id]) }}" class="btn btn-outline btn-xs">{{ __('messages.edit') }}</a>
                                <form method="POST" action="{{ route('financial.types.destroy', $type) }}" class="m-0">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-outline btn-xs text-rose-600" onclick="return confirm('Padam jenis transaksi ini?')">{{ __('messages.delete') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center">-</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @else
        <section class="card">
            <h3 class="text-base font-bold text-slate-900">{{ __('messages.add_financial_transaction') }}</h3>
            <form method="POST" action="{{ route('financial.store') }}" class="mt-4 grid gap-4 md:grid-cols-2">
                @csrf

                <div>
                    <label class="label-base">{{ __('messages.transaction_remark') }}</label>
                    <input class="input-base" name="transaction_remark" value="{{ old('transaction_remark') }}">
                </div>

                <div>
                    <label class="label-base">{{ __('messages.amount') }}</label>
                    <input class="input-base" type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}" required>
                </div>

                <div>
                    <label class="label-base">{{ __('messages.financial_type') }}</label>
                    <select class="input-base" name="financial_transaction_type_id" required>
                        <option value="">-- {{ __('messages.select') }} --</option>
                        @foreach($transactionTypes as $type)
                            <option value="{{ $type->id }}" @selected((int) old('financial_transaction_type_id') === (int) $type->id)>{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="label-base">{{ __('messages.transaction_date') }}</label>
                    <input class="input-base" type="date" name="transaction_date" value="{{ old('transaction_date', now()->toDateString()) }}" required>
                </div>

                <div>
                    <label class="label-base">{{ __('messages.credit_debit') }}</label>
                    <select class="input-base" name="credit_debit" required>
                        <option value="credit" @selected(old('credit_debit') === 'credit')>{{ __('messages.credit') }}</option>
                        <option value="debit" @selected(old('credit_debit') === 'debit')>{{ __('messages.debit') }}</option>
                    </select>
                </div>

                <div>
                    <label class="label-base">{{ __('messages.related_pasti') }} ({{ __('messages.optional') }})</label>
                    <select class="input-base" name="pasti_id">
                        <option value="">-- {{ __('messages.select') }} --</option>
                        @foreach($pastis as $pasti)
                            <option value="{{ $pasti->id }}" @selected((int) old('pasti_id') === (int) $pasti->id)>
                                {{ $pasti->name }} ({{ $pasti->kawasan?->name ?? '-' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2">
                    <button class="btn btn-primary">{{ __('messages.save') }}</button>
                </div>
            </form>
        </section>

        <section class="card mt-4">
            <h3 class="text-base font-bold text-slate-900">{{ __('messages.financial_transactions') }}</h3>
            <div class="mt-3 table-wrap">
                <table class="table-base">
                    <thead>
                    <tr>
                        <th>{{ __('messages.transaction_date') }}</th>
                        <th>{{ __('messages.financial_type') }}</th>
                        <th>{{ __('messages.credit_debit') }}</th>
                        <th>{{ __('messages.amount') }}</th>
                        <th>{{ __('messages.transaction_remark') }}</th>
                        <th>{{ __('messages.related_pasti') }}</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                    @forelse($transactions as $transaction)
                        <tr>
                            <td>{{ $transaction->transaction_date?->format('d/m/Y') }}</td>
                            <td>{{ $transaction->transactionType?->name ?? '-' }}</td>
                            <td>
                                <span class="font-semibold {{ ($transaction->credit_debit ?? 'debit') === 'credit' ? 'text-emerald-700' : 'text-rose-600' }}">
                                    {{ ($transaction->credit_debit ?? 'debit') === 'credit' ? __('messages.credit') : __('messages.debit') }}
                                </span>
                            </td>
                            <td class="font-semibold {{ ($transaction->credit_debit ?? 'debit') === 'credit' ? 'text-emerald-700' : 'text-rose-600' }}">
                                RM {{ number_format((float) $transaction->amount, 2) }}
                            </td>
                            <td>{{ $transaction->transaction_remark ?? '-' }}</td>
                            <td>{{ $transaction->pasti?->name ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center">-</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $transactions->links() }}</div>
        </section>
    @endif
</x-app-layout>

