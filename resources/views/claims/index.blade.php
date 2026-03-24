<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-bold">{{ __('messages.claim') }}</h2>
    </x-slot>

    <div class="mb-4 flex flex-wrap gap-2">
        <a href="{{ route('claims.index', ['tab' => 'list']) }}" class="btn {{ $activeTab === 'list' ? 'btn-primary' : 'btn-outline' }}">
            {{ __('messages.list') }}
        </a>
        @if(auth()->user()->hasRole('guru'))
            <a href="{{ route('claims.index', ['tab' => 'submit']) }}" class="btn {{ $activeTab === 'submit' ? 'btn-primary' : 'btn-outline' }}">
                {{ __('messages.submit_claim') }}
            </a>
        @endif
        @if($canApprove)
            <a href="{{ route('claims.index', ['tab' => 'pending']) }}" class="btn {{ $activeTab === 'pending' ? 'btn-primary' : 'btn-outline' }}">
                {{ __('messages.pending_approval') }}
            </a>
        @endif
    </div>

    @if($activeTab === 'submit')
        <section class="card">
            <h3 class="text-base font-bold text-slate-900">{{ __('messages.submit_claim') }}</h3>
            <form method="POST" action="{{ route('claims.store') }}" enctype="multipart/form-data" class="mt-4 grid gap-4 md:grid-cols-2">
                @csrf
                <div>
                    <label class="label-base">{{ __('messages.notes') }}</label>
                    <textarea class="input-base min-h-[110px]" name="notes" required>{{ old('notes') }}</textarea>
                </div>
                <div>
                    <label class="label-base">{{ __('messages.amount') }}</label>
                    <input class="input-base" type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}" required>
                </div>
                <div>
                    <label class="label-base">{{ __('messages.date') }}</label>
                    <input class="input-base" type="date" name="claim_date" value="{{ old('claim_date', now()->toDateString()) }}" required>
                </div>
                <div>
                    <label class="label-base">{{ __('messages.image') }} ({{ __('messages.optional') }})</label>
                    <input class="input-base" type="file" name="image" accept="image/*">
                </div>
                <div class="md:col-span-2">
                    <button class="btn btn-primary">{{ __('messages.save') }}</button>
                </div>
            </form>
        </section>
    @elseif($activeTab === 'pending' && $canApprove)
        <section class="card">
            <h3 class="text-base font-bold text-slate-900">{{ __('messages.pending_approval') }}</h3>
            <div class="mt-3 table-wrap">
                <table class="table-base">
                    <thead>
                    <tr>
                        <th>{{ __('messages.date') }}</th>
                        <th>{{ __('messages.name') }}</th>
                        <th>{{ __('messages.pasti') }}</th>
                        <th>{{ __('messages.amount') }}</th>
                        <th>{{ __('messages.notes') }}</th>
                        <th>{{ __('messages.image') }}</th>
                        <th>{{ __('messages.actions') }}</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                    @forelse($pendingClaims as $claim)
                        <tr>
                            <td>{{ $claim->claim_date?->format('d/m/Y') }}</td>
                            <td>{{ $claim->user?->display_name ?? '-' }}</td>
                            <td>{{ $claim->pasti?->name ?? '-' }}</td>
                            <td>RM {{ number_format((float) $claim->amount, 2) }}</td>
                            <td>{{ $claim->notes }}</td>
                            <td>
                                @if($claim->image_path)
                                    <a href="{{ asset('storage/' . $claim->image_path) }}" target="_blank" class="btn btn-outline btn-xs">{{ __('messages.view') }}</a>
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                <div class="flex flex-col gap-2">
                                    <form method="POST" action="{{ route('claims.approve', $claim) }}" class="flex min-w-[220px] flex-col gap-2">
                                        @csrf
                                        <select name="payment_method" class="input-base input-sm" required>
                                            <option value="cash">{{ __('messages.cash') }}</option>
                                            <option value="transfer" selected>{{ __('messages.transfer') }}</option>
                                        </select>
                                        <input class="input-base input-sm" type="number" step="0.01" min="0.01" name="approved_amount" value="{{ number_format((float) $claim->amount, 2, '.', '') }}" required>
                                        <button class="btn btn-primary btn-xs">{{ __('messages.approve') }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('claims.destroy', $claim) }}" onsubmit="return confirm('{{ __('Adakah anda pasti mahu memadam claim ini?') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-error btn-xs w-full text-white">{{ __('messages.delete') }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center">-</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $pendingClaims->links() }}</div>
        </section>
    @else
        <section class="card">
            <h3 class="text-base font-bold text-slate-900">{{ __('messages.claim_list') }}</h3>
            <div class="mt-3 table-wrap">
                <table class="table-base">
                    <thead>
                    <tr>
                        <th>{{ __('messages.date') }}</th>
                        @unless(auth()->user()->hasRole('guru') && !auth()->user()->hasAnyRole(['master_admin', 'admin']))
                            <th>{{ __('messages.name') }}</th>
                            <th>{{ __('messages.pasti') }}</th>
                        @endunless
                        <th>{{ __('messages.amount') }}</th>
                        <th>{{ __('messages.approved_amount') }}</th>
                        <th>{{ __('messages.payment_method') }}</th>
                        <th>{{ __('messages.status') }}</th>
                        <th>{{ __('messages.image') }}</th>
                        <th>{{ __('messages.actions') }}</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                    @forelse($claims as $claim)
                        <tr>
                            <td>{{ $claim->claim_date?->format('d/m/Y') }}</td>
                            @unless(auth()->user()->hasRole('guru') && !auth()->user()->hasAnyRole(['master_admin', 'admin']))
                                <td>{{ $claim->user?->display_name ?? '-' }}</td>
                                <td>{{ $claim->pasti?->name ?? '-' }}</td>
                            @endunless
                            <td>RM {{ number_format((float) $claim->amount, 2) }}</td>
                            <td>{{ $claim->approved_amount ? 'RM '.number_format((float) $claim->approved_amount, 2) : '-' }}</td>
                            <td>
                                @if($claim->payment_method)
                                    {{ $claim->payment_method === 'cash' ? __('messages.cash') : __('messages.transfer') }}
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                @if($claim->status === 'approved')
                                    <span class="font-semibold text-emerald-700">{{ __('messages.approved') }}</span>
                                @elseif($claim->status === 'rejected')
                                    <span class="font-semibold text-rose-600">{{ __('messages.rejected') }}</span>
                                @else
                                    <span class="font-semibold text-amber-600">{{ __('messages.pending') }}</span>
                                @endif
                            </td>
                            <td>
                                @if($claim->image_path)
                                    <a href="{{ asset('storage/' . $claim->image_path) }}" target="_blank" class="btn btn-outline btn-xs">{{ __('messages.view') }}</a>
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                @if($claim->status === 'pending')
                                    @php
                                        $canDelete = false;
                                        $user = auth()->user();
                                        if ($user->hasRole('master_admin')) {
                                            $canDelete = true;
                                        } elseif ($user->hasRole('admin')) {
                                            $assignedPastiIds = $user->assignedPastis()->pluck('pastis.id')->all();
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
                                    @endphp

                                    @if($canDelete)
                                        <form method="POST" action="{{ route('claims.destroy', $claim) }}" onsubmit="return confirm('{{ __('Adakah anda pasti mahu memadam claim ini?') }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-error btn-xs text-white">{{ __('messages.delete') }}</button>
                                        </form>
                                    @else
                                        -
                                    @endif
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ auth()->user()->hasRole('guru') && !auth()->user()->hasAnyRole(['master_admin', 'admin']) ? 7 : 9 }}" class="text-center">-</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $claims->links() }}</div>
        </section>
    @endif
</x-app-layout>

