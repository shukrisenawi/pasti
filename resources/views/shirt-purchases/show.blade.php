<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold">Pembelian Baju</h2>
                <p class="text-sm text-slate-500">{{ $purchase->title }}</p>
            </div>
            <a href="{{ route('shirt-purchases.index') }}" class="btn btn-outline btn-sm">Kembali</a>
        </div>
    </x-slot>

    <div class="space-y-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-wrap gap-2">
                <button type="button" class="btn btn-primary btn-sm" data-tab-button data-target="buyers-tab">Senarai Pembeli</button>
                <button type="button" class="btn btn-outline btn-sm" data-tab-button data-target="info-tab">Maklumat Baju</button>
            </div>

            <form method="POST" action="{{ route('shirt-purchases.broadcast', $purchase) }}" class="sm:ml-auto">
                @csrf
                <button class="btn btn-primary btn-sm w-full sm:w-auto">Keluarkan Senarai</button>
            </form>
        </div>

        <div id="buyers-tab" data-tab-panel class="space-y-4">
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Nama</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Saiz</th>
                                <th class="px-4 py-3 text-right font-semibold text-slate-600">Tindakan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                @forelse($submittedResponses as $response)
                            <tr data-shirt-response-card data-response-id="{{ $response->id }}" class="align-top transition-colors duration-150 hover:bg-emerald-50/80">
                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-3">
                                        <x-avatar :guru="$response->guru" size="h-9 w-9" border="border border-slate-200" />
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <p class="font-extrabold text-slate-800">{{ $response->guru?->display_name ?? '-' }}</p>
                                                <span
                                                    data-paid-icon
                                                    class="{{ $response->approved_at ? 'inline-flex' : 'hidden' }} h-6 w-6 items-center justify-center rounded-full bg-emerald-100 text-emerald-600"
                                                    title="Dah Bayar"
                                                    aria-label="Dah Bayar"
                                                >
                                                    <svg viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4">
                                                        <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.42l-7.2 7.2a1 1 0 01-1.415 0l-3.2-3.2a1 1 0 111.414-1.42l2.493 2.494 6.493-6.494a1 1 0 011.415 0z" clip-rule="evenodd" />
                                                    </svg>
                                                </span>
                                            </div>
                                            @if($response->notes)
                                                <p class="mt-1 text-xs text-slate-500">- {{ $response->notes }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-slate-700">
                                    <span class="font-bold">{{ $response->size ?? '-' }}</span>
                                    @if($response->quantity > 1)
                                        <span class="text-slate-500">- {{ $response->quantity }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4">
                                    <div class="ml-auto w-full max-w-sm space-y-2 text-right">
                                        @if($response->approved_at)
                                            <div class="flex flex-wrap justify-end gap-2 text-xs">
                                                <span data-approved-badge class="rounded-full bg-primary/10 px-3 py-1 font-semibold text-primary">
                                                    Diluluskan
                                                </span>
                                            </div>
                                        @endif

                                        <div class="flex flex-wrap justify-end gap-2">
                                            <form method="POST" action="{{ route('shirt-purchases.responses.mark-paid', $response) }}" data-mark-paid-form @class(['hidden' => $response->paid_at !== null])>
                                                @csrf
                                                <button
                                                    data-mark-paid-button
                                                    class="btn btn-outline btn-sm px-3"
                                                    title="Sudah Bayar"
                                                    aria-label="Sudah Bayar"
                                                >
                                                    <svg viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4">
                                                        <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.42l-7.2 7.2a1 1 0 01-1.415 0l-3.2-3.2a1 1 0 111.414-1.42l2.493 2.494 6.493-6.494a1 1 0 011.415 0z" clip-rule="evenodd" />
                                                    </svg>
                                                </button>
                                            </form>

                                            <form method="POST" action="{{ route('shirt-purchases.responses.approve', $response) }}" data-approve-form @class(['hidden' => $response->paid_at === null || $response->approved_at !== null])>
                                                @csrf
                                                <button
                                                    data-approve-button
                                                    class="btn btn-primary btn-sm px-3"
                                                    title="Sahkan Bayaran"
                                                    aria-label="Sahkan Bayaran"
                                                >
                                                    <svg viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4">
                                                        <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.42l-7.2 7.2a1 1 0 01-1.415 0l-3.2-3.2a1 1 0 111.414-1.42l2.493 2.494 6.493-6.494a1 1 0 011.415 0z" clip-rule="evenodd" />
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                @empty
                            <tr>
                                <td colspan="3" class="px-4 py-8 text-center text-slate-400">
                                    Belum ada guru yang submit pembelian baju ini.
                                </td>
                            </tr>
                @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="info-tab" data-tab-panel class="hidden">
            <div class="card border-primary/10 bg-white/95">
                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div class="space-y-3">
                        <div>
                            <h3 class="text-base font-bold text-slate-900">{{ $purchase->title }}</h3>
                            <p class="mt-1 whitespace-pre-wrap text-sm text-slate-600">{{ $purchase->description ?: '-' }}</p>
                        </div>

                        @if($purchase->image_url)
                            <a href="{{ $purchase->image_url }}" target="_blank" class="block">
                                <img src="{{ $purchase->image_url }}" alt="{{ $purchase->title }}" class="h-48 w-full max-w-md rounded-2xl border border-slate-200 object-cover">
                            </a>
                        @endif

                        <div class="grid gap-2 text-sm text-slate-600 sm:grid-cols-2">
                            <div class="rounded-2xl bg-slate-50 px-4 py-3">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Jumlah Pembeli</p>
                                <p class="mt-1 text-lg font-bold text-slate-900">{{ $submittedResponses->count() }}</p>
                            </div>
                            <div class="rounded-2xl bg-slate-50 px-4 py-3">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Jumlah Kuantiti</p>
                                <p class="mt-1 text-lg font-bold text-slate-900">{{ $submittedResponses->sum('quantity') }}</p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const tabButtons = document.querySelectorAll('[data-tab-button]');
            const tabPanels = document.querySelectorAll('[data-tab-panel]');

            const activateTab = (targetId) => {
                tabButtons.forEach((button) => {
                    const isActive = button.dataset.target === targetId;
                    button.classList.toggle('btn-primary', isActive);
                    button.classList.toggle('btn-outline', !isActive);
                });

                tabPanels.forEach((panel) => {
                    panel.classList.toggle('hidden', panel.id !== targetId);
                });
            };

            tabButtons.forEach((button) => {
                button.addEventListener('click', () => activateTab(button.dataset.target));
            });

            activateTab('buyers-tab');

            document.querySelectorAll('[data-mark-paid-form]').forEach((form) => {
                form.addEventListener('submit', async (event) => {
                    event.preventDefault();

                    const button = form.querySelector('[data-mark-paid-button]');
                    const card = form.closest('[data-shirt-response-card]');
                    const paidIcon = card?.querySelector('[data-paid-icon]');
                    const markPaidForm = card?.querySelector('[data-mark-paid-form]');
                    const approveForm = card?.querySelector('[data-approve-form]');
                    const approveButton = card?.querySelector('[data-approve-button]');

                    if (!button || button.disabled) {
                        return;
                    }

                    const originalText = button.textContent;
                    button.disabled = true;
                    button.textContent = 'Menyimpan...';

                    try {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value,
                            },
                        });

                        if (!response.ok) {
                            throw new Error('Gagal kemas kini bayaran.');
                        }

                        const payload = await response.json();

                        if (payload.response?.paid && paidIcon) {
                            paidIcon.classList.remove('hidden');
                            paidIcon.classList.add('inline-flex');
                        }

                        if (markPaidForm) {
                            markPaidForm.classList.add('hidden');
                        }

                        if (approveForm && !payload.response?.approved) {
                            approveForm.classList.remove('hidden');
                        }

                        if (approveButton && !payload.response?.approved) {
                            approveButton.disabled = false;
                        }

                        button.textContent = 'Sudah Ditanda';
                    } catch (error) {
                        button.disabled = false;
                        button.textContent = originalText;
                        window.alert(error.message || 'Gagal kemas kini bayaran.');
                    }
                });
            });
        });
    </script>
</x-app-layout>
