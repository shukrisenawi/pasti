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
                <div class="md:col-span-2">
                    <label class="label-base">Avatar</label>
                    <div class="mt-2 flex items-center gap-4">
                        <img src="{{ $guru->avatar_url }}" alt="{{ $guru->display_name }}" class="h-16 w-16 rounded-full border border-base-300 object-cover">
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
            </div>

            <div class="flex gap-2">
                <button class="btn btn-primary">{{ __('messages.save') }}</button>
                <a href="{{ route('users.gurus.index') }}" class="btn btn-outline">{{ __('messages.cancel') }}</a>
            </div>
        </form>
    </div>
</x-app-layout>
