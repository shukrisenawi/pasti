<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold">{{ __('messages.ajk_program') }}</h2>
                <p class="text-sm text-slate-500">{{ __('messages.ajk_program_subtitle') }}</p>
            </div>
        </div>
    </x-slot>

    <div class="mb-4 flex flex-wrap gap-2">
        <a
            href="{{ route('ajk-program.index', ['tab' => 'assignments', 'user_id' => $selectedUser?->id, 'edit_position' => request('edit_position')]) }}"
            class="btn {{ $activeTab === 'assignments' ? 'btn-primary' : 'btn-outline' }}"
        >
            {{ __('messages.user_position_assignment') }}
        </a>
        @role('master_admin')
            <a
                href="{{ route('ajk-program.index', ['tab' => 'positions', 'user_id' => $selectedUser?->id]) }}"
                class="btn {{ $activeTab === 'positions' ? 'btn-primary' : 'btn-outline' }}"
            >
                {{ __('messages.ajk_position') }}
            </a>
        @endrole
    </div>

    @if($activeTab === 'positions')
        @role('master_admin')
            <section class="card">
                <h3 class="text-base font-bold text-slate-900">
                    {{ $editingPosition ? __('messages.edit') : __('messages.new') }} {{ __('messages.ajk_position') }}
                </h3>
                <form
                    method="POST"
                    action="{{ $editingPosition ? route('ajk-program.positions.update', $editingPosition) : route('ajk-program.positions.store') }}"
                    class="mt-4 space-y-4"
                >
                    @csrf
                    @if($editingPosition)
                        @method('PUT')
                    @endif

                    <div>
                        <label class="label-base">{{ __('messages.position_name') }}</label>
                        <input
                            class="input-base"
                            name="name"
                            value="{{ old('name', $editingPosition?->name) }}"
                            required
                        >
                    </div>

                    <div>
                        <label class="label-base">{{ __('messages.position_description') }}</label>
                        <textarea class="input-base" name="description" rows="3" required>{{ old('description', $editingPosition?->description) }}</textarea>
                    </div>

                    <div class="flex gap-2">
                        <button class="btn btn-primary">{{ __('messages.save') }}</button>
                        @if($editingPosition)
                            <a href="{{ route('ajk-program.index', ['tab' => 'positions', 'user_id' => $selectedUser?->id]) }}" class="btn btn-outline">{{ __('messages.cancel') }}</a>
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
                                        <a href="{{ route('ajk-program.index', ['tab' => 'positions', 'edit_position' => $position->id, 'user_id' => $selectedUser?->id]) }}" class="btn btn-ghost btn-xs btn-circle text-amber-600" title="{{ __('messages.edit') }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                            </svg>
                                        </a>
                                        <form method="POST" action="{{ route('ajk-program.positions.destroy', $position) }}" class="m-0 inline">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-ghost btn-xs btn-circle text-rose-600" onclick="return confirm('Delete?')" title="{{ __('messages.delete') }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">-</p>
                        @endforelse
                    </div>
                </div>
            </section>
        @endrole
    @else
        <section class="card">
            <h3 class="text-base font-bold text-slate-900">{{ __('messages.user_position_assignment') }}</h3>

            @if($selectedUser)
                <form method="GET" action="{{ route('ajk-program.index') }}" class="mt-4">
                    <input type="hidden" name="tab" value="assignments">
                    <label class="label-base">{{ __('messages.select_user') }}</label>
                    <select name="user_id" class="input-base" onchange="this.form.submit()">
                        @foreach($users as $item)
                            <option value="{{ $item->id }}" @selected((int) $selectedUser->id === (int) $item->id)>
                                {{ $item->display_name }} ({{ $item->email }})
                            </option>
                        @endforeach
                    </select>
                </form>

                <form method="POST" action="{{ route('ajk-program.assignments.update', $selectedUser) }}" class="mt-4 space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-sm font-semibold text-slate-700">{{ __('messages.selected_user') }}</p>
                        <p class="mt-1 text-base font-bold text-slate-900">{{ $selectedUser->display_name }}</p>
                        <p class="text-sm text-slate-500">{{ $selectedUser->email }}</p>
                    </div>

                    <div>
                        <p class="label-base">{{ __('messages.select_positions') }}</p>
                        <div class="mt-2 space-y-2">
                            @php
                                $selectedPositionIds = old('position_ids', $selectedUser->ajkPositions->pluck('id')->all());
                            @endphp
                            @forelse($positions as $position)
                                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-white p-3 hover:border-primary/40">
                                    <input
                                        type="checkbox"
                                        name="position_ids[]"
                                        value="{{ $position->id }}"
                                        class="mt-1 h-4 w-4 rounded border-slate-300 text-primary focus:ring-primary"
                                        @checked(in_array($position->id, $selectedPositionIds))
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
                        <button class="btn btn-primary">{{ __('messages.save') }}</button>
                    </div>
                </form>
            @else
                <p class="mt-4 text-sm text-slate-500">-</p>
            @endif
        </section>
    @endif
</x-app-layout>
