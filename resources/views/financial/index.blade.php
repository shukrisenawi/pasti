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
    </div>

    @if($activeTab === 'ringkasan')
        <div class="grid gap-4 lg:grid-cols-2">
            <section class="card">
                <p class="text-sm font-semibold text-slate-600">{{ __('messages.current_balance') }}</p>
                <p class="mt-2 text-3xl font-black {{ $currentBalance < 0 ? 'text-rose-600' : 'text-emerald-700' }}">
                    RM {{ number_format($currentBalance, 2) }}
                </p>
            </section>

            <section class="card">
                <h3 class="text-base font-bold text-slate-900">{{ __('messages.debtor_pasti_list') }}</h3>
                <div class="mt-3 table-wrap">
                    <table class="table-base">
                        <thead>
                        <tr>
                            <th>{{ __('messages.kawasan') }}</th>
                            <th>{{ __('messages.current_debt') }}</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                        @forelse($debtByKawasan as $item)
                            <tr>
                                <td>{{ $item->kawasan_name }}</td>
                                <td class="font-semibold text-rose-600">RM {{ number_format($item->total_debt, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="text-center">-</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <section class="card mt-4">
            <h3 class="text-base font-bold text-slate-900">{{ __('messages.debtor_pasti_list') }}</h3>
            <div class="mt-3 table-wrap">
                <table class="table-base">
                    <thead>
                    <tr>
                        <th>{{ __('messages.pasti') }}</th>
                        <th>{{ __('messages.kawasan') }}</th>
                        <th>{{ __('messages.current_debt') }}</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                    @forelse($debtorPastis as $item)
                        <tr>
                            <td>{{ $item->pasti_name }}</td>
                            <td>{{ $item->kawasan_name }}</td>
                            <td class="font-semibold text-rose-600">RM {{ number_format(abs($item->balance), 2) }}</td>
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
                    <label class="label-base">{{ __('messages.pasti') }}</label>
                    <select class="input-base" name="pasti_id" required>
                        <option value="">-- {{ __('messages.select') }} --</option>
                        @foreach($pastis as $pasti)
                            <option value="{{ $pasti->id }}" @selected((int) old('pasti_id') === (int) $pasti->id)>
                                {{ $pasti->name }} ({{ $pasti->kawasan?->name ?? '-' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="label-base">{{ __('messages.transaction_date') }}</label>
                    <input class="input-base" type="date" name="transaction_date" value="{{ old('transaction_date', now()->toDateString()) }}" required>
                </div>

                <div>
                    <label class="label-base">{{ __('messages.transaction_type') }}</label>
                    <select class="input-base" name="transaction_type" required>
                        <option value="masuk" @selected(old('transaction_type') === 'masuk')>{{ __('messages.income') }}</option>
                        <option value="keluar" @selected(old('transaction_type') === 'keluar')>{{ __('messages.expense') }}</option>
                    </select>
                </div>

                <div>
                    <label class="label-base">{{ __('messages.amount') }}</label>
                    <input class="input-base" type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}" required>
                </div>

                <div>
                    <label class="label-base">{{ __('messages.amount_remark') }}</label>
                    <input class="input-base" name="amount_remark" value="{{ old('amount_remark') }}">
                </div>

                <div>
                    <label class="label-base">{{ __('messages.transaction_remark') }}</label>
                    <input class="input-base" name="transaction_remark" value="{{ old('transaction_remark') }}">
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
                        <th>{{ __('messages.pasti') }}</th>
                        <th>{{ __('messages.kawasan') }}</th>
                        <th>{{ __('messages.transaction_type') }}</th>
                        <th>{{ __('messages.amount') }}</th>
                        <th>{{ __('messages.amount_remark') }}</th>
                        <th>{{ __('messages.transaction_remark') }}</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                    @forelse($transactions as $transaction)
                        <tr>
                            <td>{{ $transaction->transaction_date?->format('d/m/Y') }}</td>
                            <td>{{ $transaction->pasti?->name ?? '-' }}</td>
                            <td>{{ $transaction->pasti?->kawasan?->name ?? '-' }}</td>
                            <td>
                                <span class="{{ $transaction->transaction_type === 'masuk' ? 'text-emerald-700' : 'text-rose-600' }} font-semibold">
                                    {{ $transaction->transaction_type === 'masuk' ? __('messages.income') : __('messages.expense') }}
                                </span>
                            </td>
                            <td class="{{ $transaction->transaction_type === 'masuk' ? 'text-emerald-700' : 'text-rose-600' }} font-semibold">
                                RM {{ number_format((float) $transaction->amount, 2) }}
                            </td>
                            <td>{{ $transaction->amount_remark ?? '-' }}</td>
                            <td>{{ $transaction->transaction_remark ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center">-</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $transactions->links() }}</div>
        </section>
    @endif
</x-app-layout>
