<div>
    @if($notice)
        <div class="alert alert-success mb-4">{{ $notice }}</div>
    @endif

    @if($isGuruOnly)
        <div class="grid grid-cols-1 gap-4 md:hidden">
            @forelse($scores as $score)
                <div class="bg-white rounded-2xl p-5 shadow-card border border-slate-50 flex flex-col justify-between">
                    <div class="flex justify-between items-start mb-4">
                        <div class="pr-2">
                            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 leading-none mb-1.5">{{ $score->year }}</p>
                            <h3 class="font-extrabold text-slate-900 leading-tight">{{ $score->titleOption?->title ?? '-' }}</h3>
                        </div>
                        <div class="text-right">
                            <div class="text-[10px] font-bold text-primary uppercase leading-none mb-1">Skor</div>
                            <div class="text-2xl font-black text-primary">{{ number_format((float) $score->score, 2) }}</div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 text-xs text-slate-400 mt-2 border-t border-slate-50 pt-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span>{{ __('messages.updated_at_label') }}: {{ $score->updated_at?->format('d/m/Y H:i') }}</span>
                    </div>
                </div>
            @empty
                <div class="text-center py-10 bg-white rounded-2xl border-2 border-dashed border-slate-100 text-slate-400 font-medium font-manrope">
                    -
                </div>
            @endforelse
        </div>

        <div class="table-wrap hidden md:block">
            <table class="table-base">
                <thead>
                <tr>
                    <th>{{ __('messages.title') }}</th>
                    <th>{{ __('messages.year') }}</th>
                    <th class="text-center">{{ __('messages.total_score') }}</th>
                    <th class="text-right">{{ __('messages.updated_at_label') }}</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                @forelse($scores as $score)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="font-semibold text-slate-700">{{ $score->titleOption?->title ?? '-' }}</td>
                        <td class="text-slate-500 font-medium">{{ $score->year }}</td>
                        <td class="text-center font-bold text-primary">{{ number_format((float) $score->score, 2) }}</td>
                        <td class="text-right text-slate-400 text-sm font-medium">{{ $score->updated_at?->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center py-8 text-slate-400">-</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    @else
        <div class="mb-4 flex flex-wrap gap-2">
            <button type="button" wire:click="switchTab('scores')" class="btn {{ $activeTab === 'scores' ? 'btn-primary' : 'btn-outline' }}">
                {{ __('messages.total_score') }}
            </button>
            @if($canManageTitleOptions)
                <button type="button" wire:click="switchTab('title-options')" class="btn {{ $activeTab === 'title-options' ? 'btn-primary' : 'btn-outline' }}">
                    {{ __('messages.add_pemarkahan_title_option') }}
                </button>
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
                <div class="grid gap-4 md:grid-cols-3 md:items-end">
                    <div>
                        <label class="label-base">{{ __('messages.title') }}</label>
                        <select class="input-base" wire:model.live="selectedTitleOptionId">
                            <option value="0">-- {{ __('messages.select') }} --</option>
                            @foreach($titleOptions as $option)
                                <option value="{{ $option->id }}">{{ $option->title }}</option>
                            @endforeach
                        </select>
                        @error('selectedTitleOptionId')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="label-base">{{ __('messages.year') }}</label>
                        <input class="input-base" type="number" min="2000" max="2100" wire:model.live="selectedYear">
                        @error('selectedYear')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <button type="button" class="btn btn-outline" disabled>{{ __('messages.view') }}</button>
                    </div>
                </div>
            </div>
        @endif

        @if($activeTab === 'scores' && $pastis->isNotEmpty() && $selectedTitleOptionId > 0)
            <div class="card mt-4">
                <form wire:submit.prevent="saveScores">
                    <div class="table-wrap">
                        <table class="table-base">
                            <thead>
                            <tr>
                                <th>{{ __('messages.name') }}</th>
                                <th>{{ __('messages.kawasan') }}</th>
                                <th>{{ __('messages.total_score') }}</th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                            @foreach($pastis as $pasti)
                                <tr>
                                    <td>{{ $pasti->name }}</td>
                                    <td>{{ $pasti->kawasan?->name ?? '-' }}</td>
                                    <td>
                                        <input
                                            class="input-base"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            wire:model.defer="scoresInput.{{ $pasti->id }}"
                                            placeholder="0.00"
                                        >
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>

                    @error('scoresInput.*')
                        <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                    @enderror

                    <div class="mt-4">
                        <button class="btn btn-primary" type="submit">{{ __('messages.save') }}</button>
                    </div>
                </form>
            </div>
        @endif
    @endif
</div>
