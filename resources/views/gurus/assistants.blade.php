<x-app-layout>
    @php
        $isSelfMode = $isSelfMode ?? false;
        $listRoute = $isSelfMode
            ? route('guru-assistants.index', ['tab' => 'list'])
            : route('users.gurus.assistants', ['users_guru' => $guru, 'tab' => 'list']);
        $addRoute = $isSelfMode
            ? route('guru-assistants.index', ['tab' => 'add'])
            : route('users.gurus.assistants', ['users_guru' => $guru, 'tab' => 'add']);
        $storeRoute = $isSelfMode
            ? route('guru-assistants.store')
            : route('users.gurus.assistants.store', $guru);
        $backRoute = $isSelfMode ? route('dashboard') : route('users.gurus.index');
    @endphp

    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold">Pembantu Guru</h2>
                <p class="text-sm text-slate-500">{{ $guru->display_name }} · {{ $guru->pasti?->name ?? '-' }}</p>
            </div>
            <a href="{{ $backRoute }}" class="btn btn-outline btn-sm">Kembali</a>
        </div>
    </x-slot>

    @if($errors->has('assistant'))
        <div class="alert alert-error mb-4">{{ $errors->first('assistant') }}</div>
    @endif

    <div class="mb-6 flex p-1 bg-slate-100 rounded-xl w-fit">
        <a href="{{ $listRoute }}"
           class="px-4 py-2 rounded-lg text-sm font-semibold transition-all {{ $tab === 'list' ? 'bg-white shadow-sm text-primary' : 'text-slate-500 hover:text-slate-700' }}">
            Senarai Pembantu
            <span class="ml-1 opacity-60">({{ $assistants->total() }})</span>
        </a>
        <a href="{{ $addRoute }}"
           class="px-4 py-2 rounded-lg text-sm font-semibold transition-all {{ $tab === 'add' ? 'bg-white shadow-sm text-primary' : 'text-slate-500 hover:text-slate-700' }}">
            Tambah Pembantu
        </a>
    </div>

    @if($tab === 'add')
        <div class="card">
            <form method="POST" action="{{ $storeRoute }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="label-base">{{ __('messages.name') }}</label>
                        <input class="input-base" name="name" value="{{ old('name') }}" required>
                    </div>
                    <div>
                        <label class="label-base">{{ __('messages.email') }}</label>
                        <input class="input-base" type="email" name="email" value="{{ old('email') }}">
                        <p class="mt-1 text-xs text-slate-500">Opsyenal untuk pembantu guru.</p>
                    </div>
                    <div>
                        <label class="label-base">{{ __('messages.phone') }}</label>
                        <input class="input-base" name="phone" value="{{ old('phone') }}">
                    </div>
                    <div>
                        <label class="label-base">Tarikh menjadi Pembantu Guru</label>
                        <input class="input-base" type="date" name="joined_at" value="{{ old('joined_at') }}">
                    </div>
                    <div class="md:col-span-2">
                        <label class="label-base">Avatar</label>
                        <input type="file" name="avatar" accept=".jpg,.jpeg,.png,.webp,image/*" class="file-input w-full" required>
                        <p class="mt-1 text-xs text-slate-500">Format: JPG, PNG, WEBP (maks 7MB).</p>
                    </div>
                    <div class="flex items-center gap-2 pt-1">
                        <input id="active" type="checkbox" name="active" value="1" @checked(old('active', true))>
                        <label for="active" class="label-base">{{ __('messages.active') }}</label>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button class="btn btn-primary">Simpan Pembantu</button>
                    <a href="{{ $listRoute }}" class="btn btn-outline">{{ __('messages.cancel') }}</a>
                </div>
            </form>
        </div>
    @else
        @if($assistants->count())
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach($assistants as $assistant)
                    <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="flex items-center gap-3">
                            <x-avatar :guru="$assistant" size="h-11 w-11" rounded="rounded-xl" border="border border-slate-200" />
                            <div class="min-w-0">
                                <h3 class="truncate text-base font-extrabold text-slate-800">{{ $assistant->display_name }}</h3>
                                <p class="truncate text-sm text-slate-500">{{ $assistant->phone ?: '-' }}</p>
                            </div>
                        </div>

                        <div class="mt-3 text-sm text-slate-600">
                            <p>
                                <span class="font-semibold text-slate-700">{{ __('messages.status') }}:</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $assistant->active ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-800' }}">
                                    {{ $assistant->active ? __('messages.active') : __('messages.inactive') }}
                                </span>
                            </p>
                        </div>

                        <div class="mt-4 flex items-center gap-2">
                            <a href="{{ $isSelfMode ? route('guru-assistants.edit', $assistant) : route('users.gurus.edit', $assistant) }}" class="btn btn-outline btn-sm h-8 w-8 p-0" title="{{ __('messages.edit') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 3.487a2.1 2.1 0 0 1 2.971 2.971L8.36 17.93 4 19l1.07-4.36 11.792-11.153Z"/>
                                </svg>
                            </a>
                            <form method="POST" action="{{ $isSelfMode ? route('guru-assistants.destroy', $assistant) : route('users.gurus.destroy', $assistant) }}" class="inline m-0">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-ghost btn-sm h-8 w-8 p-0 text-rose-600" onclick="return confirm('Padam pembantu guru ini?')" title="{{ __('messages.delete') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h16M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2m-8 0 1 12a1 1 0 0 0 1 .917h6a1 1 0 0 0 1-.917L17 7"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 11v6M14 11v6"/>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </article>
                @endforeach
            </div>
            <div class="mt-4">{{ $assistants->links() }}</div>
        @else
            <div class="card text-center text-slate-500">Tiada pembantu guru.</div>
        @endif
    @endif
</x-app-layout>
