<div>
    @if($notice)
        <div class="alert alert-success mb-4">{{ $notice }}</div>
    @endif

    @if($isGuruOnly)
        <div class="card">
            <h3 class="text-base font-bold">Markah PASTI Saya</h3>
            <div class="mt-4 space-y-3">
                @forelse($savedScores as $score)
                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                        <p class="text-sm text-slate-600">Tajuk: <span class="font-semibold text-slate-800">{{ $score->titleOption?->title ?? '-' }}</span></p>
                        <p class="mt-1 text-sm text-slate-600">Markah: <span class="font-bold text-primary">{{ number_format((float) $score->score, 2) }}</span></p>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">-</p>
                @endforelse
            </div>
        </div>
    @else
        <div class="mb-4 flex flex-wrap gap-2">
            <a href="{{ route('pemarkahan.index', ['tab' => 'scores']) }}" class="btn {{ $activeTab === 'scores' ? 'btn-primary' : 'btn-outline' }}">
                Senarai Markah
            </a>
            <a href="{{ route('pemarkahan.index', ['tab' => 'pasti-scores']) }}" class="btn {{ $activeTab === 'pasti-scores' ? 'btn-primary' : 'btn-outline' }}">
                Letak Markah PASTI
            </a>
            @if($canManageTitleOptions)
                <a href="{{ route('pemarkahan.index', ['tab' => 'title-options']) }}" class="btn {{ $activeTab === 'title-options' ? 'btn-primary' : 'btn-outline' }}">
                    {{ __('messages.add_pemarkahan_title_option') }}
                </a>
            @endif
        </div>

        @if($activeTab === 'title-options' && $canManageTitleOptions)
            <div class="card">
                <h3 class="text-base font-bold">{{ __('messages.add_pemarkahan_title_option') }}</h3>
                <form wire:submit.prevent="saveTitleOption" class="mt-3 flex flex-wrap gap-2">
                    <input class="input-base max-w-md" wire:model.defer="newTitle" placeholder="{{ __('messages.title') }}" required>
                    <button class="btn btn-outline" type="submit">{{ __('messages.add') }}</button>
                </form>
                @error('newTitle')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror

                @if($editingTitleOptionId)
                    <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-3">
                        <p class="text-sm font-semibold text-amber-900">{{ __('messages.edit') }} {{ __('messages.title') }}</p>
                        <form wire:submit.prevent="updateTitleOption" class="mt-2 flex flex-wrap gap-2">
                            <input class="input-base max-w-md" wire:model.defer="editingTitle" placeholder="{{ __('messages.title') }}" required>
                            <button class="btn btn-primary" type="submit">{{ __('messages.save') }}</button>
                            <button class="btn btn-outline" type="button" wire:click="cancelEditTitleOption">{{ __('messages.cancel') }}</button>
                        </form>
                        @error('editingTitle')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                @endif

                <div class="mt-5">
                    <p class="text-sm font-semibold text-slate-700">{{ __('messages.list') }} {{ __('messages.title') }}</p>
                    <div class="mt-3 space-y-2">
                        @forelse($allTitleOptions as $item)
                            <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ $item->title }}</p>
                                    <p class="text-xs text-slate-500">ID: {{ $item->id }} | {{ $item->is_active ? __('messages.active') : __('messages.inactive') }}</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="button" wire:click="startEditTitleOption({{ $item->id }})" class="btn btn-outline btn-xs">{{ __('messages.edit') }}</button>
                                    <button type="button" wire:click="deleteTitleOption({{ $item->id }})" wire:confirm="Padam tajuk ini?" class="btn btn-outline btn-xs text-rose-600">{{ __('messages.delete') }}</button>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">-</p>
                        @endforelse
                    </div>
                    @error('titleOptionAction')
                        <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        @endif

        @if($activeTab === 'scores')
            <div class="card">
                <h3 class="text-base font-bold">Senarai Markah Disimpan</h3>
                <div class="mt-4 space-y-3">
                    @php
                        $scoresByPasti = $savedScores->groupBy(fn ($score) => $score->pasti_id ?? 0);
                    @endphp
                    @forelse($scoresByPasti as $pastiScores)
                        @php
                            $firstScore = $pastiScores->first();
                        @endphp
                        <div class="rounded-2xl border border-slate-200 bg-white p-4">
                            <p class="text-base font-bold text-slate-900">{{ $firstScore?->pasti?->name ?? '-' }}</p>
                            <div class="mt-2 space-y-1">
                                @foreach($pastiScores as $score)
                                    <p class="text-sm text-slate-600">
                                        <span class="font-semibold text-slate-800">{{ $score->titleOption?->title ?? '-' }}</span>:
                                        <span class="font-bold text-primary">{{ number_format((float) $score->score, 2) }}</span>
                                    </p>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">-</p>
                    @endforelse
                </div>
            </div>
        @endif

        @if($activeTab === 'pasti-scores' && $pastis->isNotEmpty())
            <div class="card mt-4">
                <form method="POST" action="{{ route('pemarkahan.store') }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="form_type" value="pasti_scores_matrix">
                    <input type="hidden" name="year" value="{{ $currentYear }}">
                    @foreach($pastis as $pasti)
                        <div class="rounded-2xl border border-slate-200 bg-white p-4">
                            <p class="text-base font-bold text-slate-900">{{ $pasti->name }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $pasti->kawasan?->name ?? '-' }}</p>

                            <div class="mt-3 grid gap-3 md:grid-cols-2">
                                @foreach($titleOptions as $option)
                                    <div>
                                        <label class="label-base">{{ $option->title }}</label>
                                        <input
                                            class="input-base"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            name="pasti_scores[{{ $pasti->id }}][{{ $option->id }}]"
                                            value="{{ old('pasti_scores.'.$pasti->id.'.'.$option->id, data_get($pastiScoresForm, $pasti->id.'.'.$option->id)) }}"
                                            placeholder="0.00"
                                        >
                                    </div>
                                @endforeach
                            </div>

                            <div class="mt-3">
                                <label class="label-base">Tahun</label>
                                <input class="input-base bg-slate-100 max-w-xs" type="text" value="{{ $currentYear }}" disabled>
                            </div>
                        </div>
                    @endforeach

                    @error('pastiScoresForm')
                        <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                    @error('pastiScoresForm.*.*')
                        <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                    @if(collect($errors->keys())->contains(fn ($key) => str_starts_with($key, 'pastiScoresForm.')))
                        <p class="mt-2 text-xs text-rose-600">Sila isi markah dalam format nombor (contoh: 10 atau 10.5).</p>
                    @endif

                    <div class="mt-4">
                        <button class="btn btn-primary" type="submit">{{ __('messages.save') }}</button>
                    </div>
                </form>
            </div>
        @endif
    @endif
</div>

