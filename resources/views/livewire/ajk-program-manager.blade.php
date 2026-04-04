<div class="space-y-4">
    @if($notice)
        <div class="alert alert-success">{{ $notice }}</div>
    @endif

    <div class="flex flex-wrap gap-2">
        <a
            href="{{ route('ajk-program.index', ['tab' => 'assignments', 'selected_user_id' => $selectedUserId, 'user_search' => $userSearch]) }}"
            class="btn {{ $activeTab === 'assignments' ? 'btn-primary' : 'btn-outline' }}"
        >
            {{ __('messages.user_position_assignment') }}
        </a>
        @if($canManagePositions)
            <a
                href="{{ route('ajk-program.index', ['tab' => 'positions', 'selected_user_id' => $selectedUserId, 'user_search' => $userSearch]) }}"
                class="btn {{ $activeTab === 'positions' ? 'btn-primary' : 'btn-outline' }}"
            >
                {{ __('messages.ajk_position') }}
            </a>
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
                @php
                    $requestedSelectedIds = collect(request()->input('selected_user_ids', []))
                        ->map(fn ($id) => (int) $id)
                        ->filter()
                        ->unique()
                        ->values();

                    if ($requestedSelectedIds->isEmpty()) {
                        $requestedSelectedIds = collect([(int) $selectedUser->id]);
                    }

                    $initialSelectedUsers = $users->whereIn('id', $requestedSelectedIds)->values();
                    $usersForJs = $users->map(fn ($item) => [
                        'id' => (int) $item->id,
                        'name' => $item->display_name,
                        'email' => $item->email,
                        'avatar' => $item->avatar_url,
                    ])->values();
                    $checkedPositionIds = collect(request()->input('position_ids', []))
                        ->map(fn ($id) => (int) $id)
                        ->filter()
                        ->unique()
                        ->values()
                        ->all();

                @endphp

                <div class="mt-4">
                    <label class="label-base">{{ __('messages.select_user') }}</label>
                    <form method="GET" action="{{ route('ajk-program.index') }}" class="space-y-2">
                        <input type="hidden" name="tab" value="assignments">
                        <div class="flex items-center gap-2">
                            <input type="text" name="user_search" value="{{ $userSearch }}" class="input-base w-full min-w-0" placeholder="Cari nama / email pengguna">
                            <button type="submit" class="btn btn-outline btn-sm">Search</button>
                        </div>
                        <select id="selected-user-picker" name="selected_user_id" class="input-base">
                            <option value="">-- Pilih pengguna --</option>
                            @foreach($users as $item)
                                <option value="{{ $item->id }}" @selected((int) $selectedUserId === (int) $item->id)>
                                    {{ $item->display_name }} ({{ $item->email }})
                                </option>
                            @endforeach
                        </select>
                    </form>
                </div>

                <form method="POST" action="{{ route('ajk-program.assignments.update') }}" class="mt-4 space-y-4">
                    @csrf

                    <div>
                        <p class="label-base">Pengguna Dipilih</p>
                        <p id="selected-users-empty" class="mt-2 text-sm text-slate-500 {{ $initialSelectedUsers->isNotEmpty() ? 'hidden' : '' }}">Tiada pengguna dipilih.</p>

                        <div id="selected-users-cards" class="mt-3 grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                            @foreach($initialSelectedUsers as $item)
                                <div data-selected-user-card class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
                                    <input type="hidden" name="selected_user_ids[]" value="{{ $item->id }}">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="flex min-w-0 items-center gap-2">
                                            <img src="{{ $item->avatar_url }}" alt="{{ $item->display_name }}" class="h-10 w-10 rounded-xl border border-slate-200 object-cover">
                                            <div class="min-w-0">
                                                <p class="truncate text-sm font-bold text-slate-900">{{ $item->display_name }}</p>
                                                <p class="truncate text-xs text-slate-500">{{ $item->email }}</p>
                                            </div>
                                        </div>
                                        <button type="button" data-remove-user="{{ $item->id }}" class="btn btn-ghost btn-xs btn-circle text-rose-600" title="Buang pengguna">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="mt-3 flex flex-wrap gap-1" data-position-tags></div>
                                </div>
                            @endforeach
                        </div>

                        @error('selected_user_ids')
                            <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <p class="label-base">{{ __('messages.select_positions') }}</p>
                        <div class="mt-2 grid gap-2 md:grid-cols-2">
                            @forelse($positions as $position)
                                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-white p-3 hover:border-primary/40">
                                    <input
                                        type="checkbox"
                                        name="position_ids[]"
                                        value="{{ $position->id }}"
                                        data-position-id="{{ $position->id }}"
                                        data-position-name="{{ $position->name }}"
                                        @checked(in_array((int) $position->id, $checkedPositionIds, true))
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

                <script id="selected-users-data" type="application/json">@json($usersForJs)</script>
                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        const picker = document.getElementById('selected-user-picker');
                        const cards = document.getElementById('selected-users-cards');
                        const emptyState = document.getElementById('selected-users-empty');
                        const dataNode = document.getElementById('selected-users-data');
                        const positionCheckboxes = Array.from(document.querySelectorAll('input[name="position_ids[]"]'));

                        if (!picker || !cards || !emptyState || !dataNode) {
                            return;
                        }

                        const users = JSON.parse(dataNode.textContent || '[]');
                        const usersById = {};
                        users.forEach(function (user) {
                            usersById[String(user.id)] = user;
                        });

                        function syncEmptyState() {
                            const hasCards = cards.querySelectorAll('[data-selected-user-card]').length > 0;
                            emptyState.classList.toggle('hidden', hasCards);
                        }

                        function hasUser(userId) {
                            return !!cards.querySelector('input[name="selected_user_ids[]"][value="' + userId + '"]');
                        }

                        function createUserCard(user) {
                            return '' +
                                '<div data-selected-user-card class="rounded-2xl border border-slate-200 bg-slate-50 p-3">' +
                                    '<input type="hidden" name="selected_user_ids[]" value="' + user.id + '">' +
                                    '<div class="flex items-start justify-between gap-2">' +
                                        '<div class="flex min-w-0 items-center gap-2">' +
                                            '<img src="' + user.avatar + '" alt="' + user.name + '" class="h-10 w-10 rounded-xl border border-slate-200 object-cover">' +
                                            '<div class="min-w-0">' +
                                                '<p class="truncate text-sm font-bold text-slate-900">' + user.name + '</p>' +
                                                '<p class="truncate text-xs text-slate-500">' + user.email + '</p>' +
                                            '</div>' +
                                        '</div>' +
                                        '<button type="button" data-remove-user="' + user.id + '" class="btn btn-ghost btn-xs btn-circle text-rose-600" title="Buang pengguna">' +
                                            '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">' +
                                                '<path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />' +
                                            '</svg>' +
                                        '</button>' +
                                    '</div>' +
                                    '<div class="mt-3 flex flex-wrap gap-1" data-position-tags></div>' +
                                '</div>';
                        }

                        function getSelectedPositions() {
                            return positionCheckboxes
                                .filter(function (checkbox) { return checkbox.checked; })
                                .map(function (checkbox) {
                                    return {
                                        id: checkbox.getAttribute('data-position-id') || checkbox.value,
                                        name: checkbox.getAttribute('data-position-name') || checkbox.value,
                                    };
                                });
                        }

                        function createPositionTag(position) {
                            return '' +
                                '<span class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2 py-1 text-xs font-semibold text-primary">' +
                                    '<span>' + position.name + '</span>' +
                                    '<button type="button" data-remove-position="' + position.id + '" class="text-rose-600" title="Buang jawatan">' +
                                        '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-3.5 w-3.5">' +
                                            '<path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />' +
                                        '</svg>' +
                                    '</button>' +
                                '</span>';
                        }

                        function renderTagsForCard(card) {
                            const tagsNode = card.querySelector('[data-position-tags]');
                            if (!tagsNode) {
                                return;
                            }

                            const selectedPositions = getSelectedPositions();
                            tagsNode.innerHTML = selectedPositions.map(createPositionTag).join('');
                        }

                        function renderTagsAllCards() {
                            cards.querySelectorAll('[data-selected-user-card]').forEach(function (card) {
                                renderTagsForCard(card);
                            });
                        }

                        function addUser(userId) {
                            if (!userId || hasUser(userId) || !usersById[userId]) {
                                return;
                            }

                            cards.insertAdjacentHTML('beforeend', createUserCard(usersById[userId]));
                            renderTagsAllCards();
                            syncEmptyState();
                        }

                        picker.addEventListener('change', function () {
                            addUser(this.value);
                        });

                        cards.addEventListener('click', function (event) {
                            const removePositionButton = event.target.closest('[data-remove-position]');
                            if (removePositionButton) {
                                const positionId = removePositionButton.getAttribute('data-remove-position');
                                const checkbox = positionCheckboxes.find(function (item) {
                                    return String(item.value) === String(positionId);
                                });

                                if (checkbox) {
                                    checkbox.checked = false;
                                }

                                renderTagsAllCards();
                                return;
                            }

                            const removeButton = event.target.closest('[data-remove-user]');
                            if (!removeButton) {
                                return;
                            }

                            const card = removeButton.closest('[data-selected-user-card]');
                            if (card) {
                                card.remove();
                                syncEmptyState();
                            }
                        });

                        positionCheckboxes.forEach(function (checkbox) {
                            checkbox.addEventListener('change', function () {
                                renderTagsAllCards();
                            });
                        });

                        renderTagsAllCards();
                        syncEmptyState();
                    });
                </script>
            @else
                <p class="mt-4 text-sm text-slate-500">-</p>
            @endif
        </section>
    @endif
</div>





