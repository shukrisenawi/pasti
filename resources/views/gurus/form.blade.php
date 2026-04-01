<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-bold">{{ $guru->exists ? __('messages.edit') : __('messages.new') }} {{ __('messages.guru') }}</h2>
    </x-slot>

    <div class="card" x-data="{ isAssistant: {{ old('is_assistant', $guru->is_assistant ?? false) ? 1 : 0 }} }">
        <form method="POST" action="{{ $guru->exists ? route('users.gurus.update', $guru) : route('users.gurus.store') }}" class="space-y-4" enctype="multipart/form-data">
            @csrf
            @if($guru->exists)
                @method('PUT')
            @endif

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="label-base">{{ __('messages.teacher_type') }}</label>
                    <select class="input-base" name="is_assistant" x-model.number="isAssistant">
                        <option value="0">{{ __('messages.main_teacher') }}</option>
                        <option value="1">{{ __('messages.assistant_teacher') }}</option>
                    </select>
                    <p class="mt-1 text-xs text-slate-500">{{ __('messages.assistant_no_access') }}</p>
                </div>
                <div></div>
                <div>
                    <label class="label-base">{{ __('messages.name') }}</label>
                    <input class="input-base" name="name" value="{{ old('name', $guru->name ?? $userModel?->name) }}" required>
                </div>
                <div x-show="isAssistant === 0" x-cloak>
                    <label class="label-base">{{ __('messages.nama_samaran') }}</label>
                    <input class="input-base" name="nama_samaran" value="{{ old('nama_samaran', $userModel?->nama_samaran) }}">
                </div>
                <div x-show="isAssistant === 0" x-cloak>
                    <label class="label-base">{{ __('messages.tarikh_lahir') }}</label>
                    <input class="input-base" type="date" name="tarikh_lahir" value="{{ old('tarikh_lahir', $userModel?->tarikh_lahir?->format('Y-m-d')) }}">
                </div>
                <div x-show="isAssistant === 0" x-cloak>
                    <label class="label-base">{{ __('messages.tarikh_exp_skim_pas') }}</label>
                    <input class="input-base" type="date" name="tarikh_exp_skim_pas" value="{{ old('tarikh_exp_skim_pas', $userModel?->tarikh_exp_skim_pas?->format('Y-m-d')) }}">
                </div>
                <div>
                    <label class="label-base">{{ __('messages.email') }}</label>
                    <input class="input-base" type="email" name="email" value="{{ old('email', $guru->email ?? $userModel?->email) }}" :required="isAssistant === 0">
                    <p class="mt-1 text-xs text-slate-500" x-show="isAssistant === 1">{{ __('messages.optional_for_assistant') }}</p>
                </div>
                <div x-show="isAssistant === 0" x-cloak>
                    <label class="label-base">{{ __('messages.password') }}</label>
                    <input class="input-base" type="password" name="password" :required="isAssistant === 0 && {{ $guru->exists ? 'false' : 'true' }}">
                    @if($guru->exists)
                        <p class="mt-1 text-xs text-slate-500">{{ __('messages.optional_password') }}</p>
                    @endif
                </div>
                <div x-show="isAssistant === 0" x-cloak>
                    <label class="label-base">{{ __('messages.password_confirmation') }}</label>
                    <input class="input-base" type="password" name="password_confirmation" :required="isAssistant === 0 && {{ $guru->exists ? 'false' : 'true' }}">
                </div>
                <div>
                    <label class="label-base">{{ __('messages.pasti') }}</label>
                    <select class="input-base" name="pasti_id">
                        <option value="">-</option>
                        @foreach($pastis as $pasti)
                            <option value="{{ $pasti->id }}" @selected((int) old('pasti_id', $guru->pasti_id) === $pasti->id)>{{ $pasti->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label-base">{{ __('messages.phone') }}</label>
                    <input class="input-base" name="phone" value="{{ old('phone', $guru->phone) }}">
                </div>
                <div>
                    <label class="label-base">{{ __('messages.marital_status') }}</label>
                    <select class="input-base" name="marital_status">
                        <option value="">- {{ __('messages.select') }} -</option>
                        <option value="single" @selected(old('marital_status', $guru->marital_status) === 'single')>{{ __('messages.single') }}</option>
                        <option value="married" @selected(old('marital_status', $guru->marital_status) === 'married')>{{ __('messages.married') }}</option>
                        <option value="widowed" @selected(old('marital_status', $guru->marital_status) === 'widowed')>{{ __('messages.widowed') }}</option>
                        <option value="divorced" @selected(old('marital_status', $guru->marital_status) === 'divorced')>{{ __('messages.divorced') }}</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="label-base">Avatar</label>
                    <div class="mt-2 flex items-center gap-4">
                        <x-avatar :guru="$guru" size="h-16 w-16" />
                        <div class="w-full">
                            <input type="file" name="avatar" accept=".jpg,.jpeg,.png,.webp,image/*" class="file-input w-full">
                            @if($guru->exists)
                                <label class="mt-2 inline-flex items-center gap-2 text-sm text-base-content/70">
                                    <input type="checkbox" name="remove_avatar" value="1" class="checkbox checkbox-sm" @checked(old('remove_avatar'))>
                                    <span>Padam gambar semasa</span>
                                </label>
                            @endif
                        </div>
                    </div>
                </div>
                <div>
                    <label class="label-base">{{ __('messages.joined_at') }}</label>
                    <input class="input-base" type="date" name="joined_at" value="{{ old('joined_at', optional($guru->joined_at)->format('Y-m-d')) }}">
                </div>
                <div class="flex items-center gap-2 pt-7">
                    <input id="active" type="checkbox" name="active" value="1" @checked(old('active', $guru->exists ? $guru->active : true))>
                    <label for="active" class="label-base">{{ __('messages.active') }}</label>
                </div>
                @unless(auth()->user()->hasRole('guru'))
                <div class="flex items-center gap-2 pt-7">
                    <input id="terima_anugerah" type="checkbox" name="terima_anugerah" value="1" @checked(old('terima_anugerah', $guru->terima_anugerah ?? false))>
                    <label for="terima_anugerah" class="label-base">
                        {{ __('messages.terima_anugerah') }}
                        <span class="ml-1 inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800">
                            <svg class="mr-1 h-3 w-3 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                            {{ __('messages.admin_only') }}
                        </span>
                    </label>
                </div>
                @endunless
            </div>

            <div class="flex gap-2">
                <button class="btn btn-primary">{{ __('messages.save') }}</button>
                <a href="{{ route('users.gurus.index') }}" class="btn btn-outline">{{ __('messages.cancel') }}</a>
            </div>
        </form>
    </div>
</x-app-layout>
