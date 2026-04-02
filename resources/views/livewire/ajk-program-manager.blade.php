<div class="space-y-4">
    @if($notice)
        <div class="alert alert-success">{{ $notice }}</div>
    @endif

    <div class="flex flex-wrap gap-2">
        <button
            type="button"
            wire:click="switchTab('assignments')"
            class="btn {{ $activeTab === 'assignments' ? 'btn-primary' : 'btn-outline' }}"
        >
            {{ __('messages.user_position_assignment') }}
        </button>
        @if($canManagePositions)
            <button
                type="button"
                wire:click="switchTab('positions')"
                class="btn {{ $activeTab === 'positions' ? 'btn-primary' : 'btn-outline' }}"
            >
                {{ __('messages.ajk_position') }}
            </button>
        @endif
    </div>

    @if($activeTab === 'positions' && $canManagePositions)
        <section class="card">
            <h3 class="text-base font-bold text-slate-900">
                {{ $editingPositionId ? __('messages.edit') : __('messages.new') }} {{ __('messages.ajk_position') }}
            </h3>

            <form wire:submit.prevent="savePosition" class="mt-4 space-y-4">
                <div>
                    <label class="label-base">{{ __('messages.position_name') }}</label>
                    <input class="input-base" wire:model.defer="positionName" required>
                    @error('positionName')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="label-base">{{ __('messages.position_description') }}</label>
                    <textarea class="input-base" wire:model.defer="positionDescription" rows="3" required></textarea>
                    @error('positionDescription')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="btn btn-primary">{{ __('messages.save') }}</button>
                    @if($editingPositionId)
                        <button type="button" wire:click="cancelEditPosition" class="btn btn-outline">{{ __('messages.cancel') }}</button>
                    @endif
                </div>
            </form>

            <div class="mt-6">
                <p class="text-sm font-semibold text-slate-700">{{ __('messages.position_list') }}</p>
                <div class="mt-3 space-y-3">
                    @forelse($positions as $position)
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-bold text-slate-900">{{ $position->name }}</p>
                                    <p class="mt-1 text-sm text-slate-600">{{ $position->description }}</p>
                                </div>
                                <div class="flex items-center gap-1">
                                    <button
                                        type="button"
                                        wire:click="editPosition({{ $position->id }})"
                                        class="btn btn-ghost btn-xs btn-circle text-amber-600"
                                        title="{{ __('messages.edit') }}"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                        </svg>
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="deletePosition({{ $position->id }})"
                                        wire:confirm="Delete?"
                                        class="btn btn-ghost btn-xs btn-circle text-rose-600"
                                        title="{{ __('messages.delete') }}"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">-</p>
                    @endforelse
                </div>
            </div>
        </section>
    @else
        <section class="card">
            <h3 class="text-base font-bold text-slate-900">{{ __('messages.user_position_assignment') }}</h3>

            @if($selectedUser)
                <div class="mt-4">
                    <label class="label-base">{{ __('messages.select_user') }}</label>
                    <select wire:model="selectedUserId" wire:change="selectUser($event.target.value)" class="input-base">
                        @foreach($users as $item)
                            <option value="{{ $item->id }}">
                                {{ $item->display_name }} ({{ $item->email }})
                            </option>
                        @endforeach
                    </select>
                    @error('selectedUserId')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <form wire:submit.prevent="saveAssignments" wire:key="assignment-form-user-{{ $selectedUser->id }}" class="mt-4 space-y-4">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-sm font-semibold text-slate-700">{{ __('messages.selected_user') }}</p>
                        <div class="mt-2 flex items-center gap-3">
                            <x-avatar :user="$selectedUser" size="h-12 w-12" rounded="rounded-2xl" border="border border-slate-200/70" />
                            <div class="min-w-0">
                                <p class="truncate text-base font-bold text-slate-900">{{ $selectedUser->display_name }}</p>
                                <p class="truncate text-sm text-slate-500">{{ $selectedUser->email }}</p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <p class="label-base">{{ __('messages.select_positions') }}</p>
                        <div class="mt-2 space-y-2">
                            @forelse($positions as $position)
                                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-white p-3 hover:border-primary/40">
                                    <input
                                        type="checkbox"
                                        wire:model="positionIds"
                                        value="{{ $position->id }}"
                                        class="mt-1 h-4 w-4 rounded border-slate-300 text-primary focus:ring-primary"
                                    >
                                    <span class="block">
                                        <span class="block text-sm font-bold text-slate-900">{{ $position->name }}</span>
                                        <span class="block text-xs text-slate-500">{{ $position->description }}</span>
                                    </span>
                                </label>
                            @empty
                                <p class="text-sm text-slate-500">-</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <button type="submit" class="btn btn-primary">{{ __('messages.save') }}</button>
                    </div>
                </form>
            @else
                <p class="mt-4 text-sm text-slate-500">-</p>
            @endif
        </section>
    @endif
</div>
