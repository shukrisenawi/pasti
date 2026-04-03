<x-app-layout>
    <x-slot name="header">
        <div class="admin-form-header">
            <div>
                <p class="admin-form-eyebrow">{{ __('messages.admin_accounts') }}</p>
                <h2 class="admin-form-title">
                    {{ $adminUser->exists ? __('messages.edit') : __('messages.new') }} {{ __('messages.admin_accounts') }}
                </h2>
                <p class="admin-form-subtitle">{{ __('messages.admin_assignment') }}</p>
            </div>
            <a href="{{ route('users.admins.index') }}" class="btn btn-ghost btn-sm rounded-full px-5">{{ __('messages.cancel') }}</a>
        </div>
    </x-slot>

    <div class="admin-form-shell">
        <div class="admin-form-panel">
            @if($adminUser->exists)
            <div class="admin-form-note">
                <span class="admin-form-note-icon" aria-hidden="true">i</span>
                <span class="text-sm md:text-[0.95rem]">
                    {{ __('messages.optional_password') }}
                </span>
            </div>
            @endif

            <form method="POST" action="{{ $adminUser->exists ? route('users.admins.update', $adminUser) : route('users.admins.store') }}" class="space-y-8">
            @csrf
            @if($adminUser->exists)
                @method('PUT')
            @endif

                <section class="admin-form-section">
                    <h3 class="admin-form-section-title">{{ __('messages.profile') }}</h3>

                    <div class="grid gap-5 md:grid-cols-2">
                        <div class="admin-field">
                            <label class="admin-field-label">{{ __('messages.name') }}</label>
                            <input class="admin-field-input" type="text" name="name" value="{{ old('name', $adminUser->name) }}" required>
                            @error('name')
                                <p class="admin-field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="admin-field">
                            <label class="admin-field-label">{{ __('messages.nama_samaran') }}</label>
                            <input class="admin-field-input" type="text" name="nama_samaran" value="{{ old('nama_samaran', $adminUser->nama_samaran) }}">
                            @error('nama_samaran')
                                <p class="admin-field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="admin-field">
                            <label class="admin-field-label">{{ __('messages.tarikh_lahir') }}</label>
                            <input class="admin-field-input" type="date" name="tarikh_lahir" value="{{ old('tarikh_lahir', $adminUser->tarikh_lahir?->format('Y-m-d')) }}">
                            @error('tarikh_lahir')
                                <p class="admin-field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="admin-field">
                            <label class="admin-field-label">{{ __('messages.tarikh_exp_skim_pas') }}</label>
                            <input class="admin-field-input" type="date" name="tarikh_exp_skim_pas" value="{{ old('tarikh_exp_skim_pas', $adminUser->tarikh_exp_skim_pas?->format('Y-m-d')) }}">
                            @error('tarikh_exp_skim_pas')
                                <p class="admin-field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="admin-field md:col-span-2">
                            <label class="admin-field-label">{{ __('messages.email') }}</label>
                            <input class="admin-field-input" type="email" name="email" value="{{ old('email', $adminUser->email) }}" required>
                            @error('email')
                                <p class="admin-field-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </section>

                <section class="admin-form-section">
                    <h3 class="admin-form-section-title">{{ __('messages.password') }}</h3>

                    <div class="grid gap-5 md:grid-cols-2">
                        <div class="admin-field">
                            <label class="admin-field-label">{{ __('messages.password') }}</label>
                            <input class="admin-field-input" type="password" name="password" {{ $adminUser->exists ? '' : 'required' }}>
                            @if($adminUser->exists)
                                <p class="admin-field-hint">{{ __('messages.optional_password') }}</p>
                            @endif
                            @error('password')
                                <p class="admin-field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="admin-field">
                            <label class="admin-field-label">{{ __('messages.password_confirmation') }}</label>
                            <input class="admin-field-input" type="password" name="password_confirmation" {{ $adminUser->exists ? '' : 'required' }}>
                        </div>
                    </div>
                </section>

                <section class="admin-form-section" x-data="{ assignmentScope: '{{ old('assignment_scope', ($adminUser->exists && count($selectedPastis) !== $pastiCount) ? 'selected' : 'all') }}' }">
                    <h3 class="admin-form-section-title">{{ __('messages.admin_assignment') }}</h3>
                    <div class="admin-field">
                        <label class="admin-field-label">Skop Penugasan PASTI</label>
                        <div class="mt-2 flex flex-wrap gap-6">
                            <label class="inline-flex cursor-pointer items-center gap-2">
                                <input type="radio" name="assignment_scope" value="all" x-model="assignmentScope" class="h-4 w-4 border-slate-300 text-primary focus:ring-primary/30">
                                <span class="text-sm font-medium text-slate-700">{{ __('messages.all_pasti') }}</span>
                            </label>
                            <label class="inline-flex cursor-pointer items-center gap-2">
                                <input type="radio" name="assignment_scope" value="selected" x-model="assignmentScope" class="h-4 w-4 border-slate-300 text-primary focus:ring-primary/30">
                                <span class="text-sm font-medium text-slate-700">{{ __('messages.select_pasti') }}</span>
                            </label>
                        </div>
                        @error('assignment_scope')
                            <p class="admin-field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="admin-field" x-show="assignmentScope === 'selected'" x-cloak x-transition>
                        <label class="admin-field-label">
                            {{ __('messages.admin_assignment') }}
                            <span class="admin-field-label-sub">{{ __('messages.pasti') }}</span>
                        </label>
                        <select class="admin-select-multi" name="pasti_ids[]" multiple :required="assignmentScope === 'selected'">
                            @foreach($pastis as $pasti)
                                <option value="{{ $pasti->id }}" @selected(in_array($pasti->id, old('pasti_ids', $selectedPastis), true))>{{ $pasti->name }}</option>
                            @endforeach
                        </select>
                        <p class="admin-field-hint">Tekan Ctrl atau Command untuk pilih lebih daripada satu PASTI.</p>
                        @error('pasti_ids')
                            <p class="admin-field-error">{{ $message }}</p>
                        @enderror
                        @error('pasti_ids.*')
                            <p class="admin-field-error">{{ $message }}</p>
                        @enderror
                    </div>
                </section>

                <div class="admin-form-actions">
                    <a href="{{ route('users.admins.index') }}" class="btn btn-ghost rounded-full px-6">{{ __('messages.cancel') }}</a>
                    <button class="btn btn-primary rounded-full px-7">{{ __('messages.save') }}</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

