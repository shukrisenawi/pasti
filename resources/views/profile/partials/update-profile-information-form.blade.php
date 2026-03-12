<section>
    <header>
        <h2 class="text-lg font-semibold text-slate-900">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-slate-500">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6" enctype="multipart/form-data">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="avatar" :value="__('Avatar')" />
            <div class="mt-2 flex items-center gap-4">
                <img src="{{ $user->avatar_url }}" alt="{{ $user->display_name }}" class="h-16 w-16 rounded-2xl border border-slate-200 object-cover">
                <div class="w-full">
                    <input id="avatar" name="avatar" type="file" accept=".jpg,.jpeg,.png,.webp,image/*" class="file-input w-full">
                    <label class="mt-2 inline-flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" name="remove_avatar" value="1" class="checkbox checkbox-sm" @checked(old('remove_avatar'))>
                        <span>{{ __('Remove current avatar') }}</span>
                    </label>
                </div>
            </div>
            <x-input-error class="mt-2" :messages="$errors->get('avatar')" />
        </div>

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="nama_samaran" :value="__('messages.nama_samaran')" />
            <x-text-input id="nama_samaran" name="nama_samaran" type="text" class="mt-1 block w-full" :value="old('nama_samaran', $user->nama_samaran)" />
            <x-input-error class="mt-2" :messages="$errors->get('nama_samaran')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="mt-2 text-sm text-slate-600">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="btn-link text-sm">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 text-sm font-medium text-success">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-slate-500"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
