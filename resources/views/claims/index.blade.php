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

            @if($pendingClaims->count())
                <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach($pendingClaims as $claim)
                        <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                            <div class="space-y-1 text-sm text-slate-600">
                                <p><span class="font-semibold text-slate-700">{{ __('messages.date') }}:</span> {{ $claim->claim_date?->format('d/m/Y') }}</p>
                                <p><span class="font-semibold text-slate-700">{{ __('messages.name') }}:</span> {{ $claim->user?->display_name ?? '-' }}</p>
                                <p><span class="font-semibold text-slate-700">{{ __('messages.pasti') }}:</span> {{ $claim->pasti?->name ?? '-' }}</p>
                                <p><span class="font-semibold text-slate-700">{{ __('messages.amount') }}:</span> RM {{ number_format((float) $claim->amount, 2) }}</p>
                                <p><span class="font-semibold text-slate-700">{{ __('messages.notes') }}:</span> {{ $claim->notes }}</p>
                            </div>

                            <div class="mt-3">
                                @if($claim->image_path)
                                    <a href="{{ asset('uploads/' . $claim->image_path) }}" target="_blank" class="btn btn-outline btn-xs">{{ __('messages.view') }} {{ __('messages.image') }}</a>
                                @else
                                    <span class="text-xs text-slate-400">{{ __('messages.image') }}: -</span>
                                @endif
                            </div>

                            <div class="mt-4 space-y-2 border-t border-slate-100 pt-3">
                                <form method="POST" action="{{ route('claims.approve', $claim) }}" class="space-y-2">
                                    @csrf
                                    <select name="payment_method" class="input-base input-sm" required>
                                        <option value="cash">{{ __('messages.cash') }}</option>
                                        <option value="transfer" selected>{{ __('messages.transfer') }}</option>
                                    </select>
                                    <input class="input-base input-sm" type="number" step="0.01" min="0.01" name="approved_amount" value="{{ number_format((float) $claim->amount, 2, '.', '') }}" required>
                                    <button class="btn btn-primary btn-sm w-full">{{ __('messages.approve') }}</button>
                                </form>
                                <form method="POST" action="{{ route('claims.destroy', $claim) }}" onsubmit="return confirm('{{ __('Adakah anda pasti mahu memadam claim ini?') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-error btn-sm w-full text-white">{{ __('messages.delete') }}</button>
                                </form>
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="mt-4 text-center text-slate-500">-</div>
            @endif

            <div class="mt-4">{{ $pendingClaims->links() }}</div>
        </section>
    @else
        <section class="card">
            <h3 class="text-base font-bold text-slate-900">{{ __('messages.claim_list') }}</h3>

            @if($claims->count())
                <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach($claims as $claim)
                        <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                            <div class="space-y-1 text-sm text-slate-600">
                                <p><span class="font-semibold text-slate-700">{{ __('messages.date') }}:</span> {{ $claim->claim_date?->format('d/m/Y') }}</p>
                                @unless(auth()->user()->hasRole('guru') && !auth()->user()->hasAnyRole(['master_admin', 'admin']))
                                    <p><span class="font-semibold text-slate-700">{{ __('messages.name') }}:</span> {{ $claim->user?->display_name ?? '-' }}</p>
                                    <p><span class="font-semibold text-slate-700">{{ __('messages.pasti') }}:</span> {{ $claim->pasti?->name ?? '-' }}</p>
                                @endunless
                                <p><span class="font-semibold text-slate-700">{{ __('messages.amount') }}:</span> RM {{ number_format((float) $claim->amount, 2) }}</p>
                                <p><span class="font-semibold text-slate-700">{{ __('messages.approved_amount') }}:</span> {{ $claim->approved_amount ? 'RM '.number_format((float) $claim->approved_amount, 2) : '-' }}</p>
                                <p>
                                    <span class="font-semibold text-slate-700">{{ __('messages.payment_method') }}:</span>
                                    @if($claim->payment_method)
                                        {{ $claim->payment_method === 'cash' ? __('messages.cash') : __('messages.transfer') }}
                                    @else
                                        -
                                    @endif
                                </p>
                                <p>
                                    <span class="font-semibold text-slate-700">{{ __('messages.status') }}:</span>
                                    @if($claim->status === 'approved')
                                        <span class="font-semibold text-emerald-700">{{ __('messages.approved') }}</span>
                                    @elseif($claim->status === 'rejected')
                                        <span class="font-semibold text-rose-600">{{ __('messages.rejected') }}</span>
                                    @else
                                        <span class="font-semibold text-amber-600">{{ __('messages.pending') }}</span>
                                    @endif
                                </p>
                            </div>

                            <div class="mt-3 flex items-center gap-2">
                                @if($claim->image_path)
                                    <a href="{{ asset('uploads/' . $claim->image_path) }}" target="_blank" class="btn btn-outline btn-xs">{{ __('messages.view') }} {{ __('messages.image') }}</a>
                                @else
                                    <span class="text-xs text-slate-400">{{ __('messages.image') }}: -</span>
                                @endif

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
                                        <form method="POST" action="{{ route('claims.destroy', $claim) }}" onsubmit="return confirm('{{ __('Adakah anda pasti mahu memadam claim ini?') }}')" class="m-0">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-error btn-xs text-white">{{ __('messages.delete') }}</button>
                                        </form>
                                    @endif
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="mt-4 text-center text-slate-500">-</div>
            @endif

            <div class="mt-4">{{ $claims->links() }}</div>
        </section>
    @endif
</x-app-layout>
