<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        @if (!empty($lastLoginUser))
            <div class="rounded-3xl border border-slate-200 bg-slate-50/90 p-4">
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <x-avatar :user="$lastLoginUser" size="h-14 w-14" rounded="rounded-2xl" />
                        @if(($lastLoginUser->login_unread_notifications_count ?? 0) > 0)
                            <span class="badge badge-error badge-xs absolute -right-1 -top-1" style="z-index: 20;">
                                {{ ($lastLoginUser->login_unread_notifications_count ?? 0) > 99 ? '99+' : $lastLoginUser->login_unread_notifications_count }}
                            </span>
                        @endif
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm text-slate-500">{{ __('messages.continue_as') }}</p>
                        <p class="truncate font-semibold text-slate-900">{{ $lastLoginUser->email }}</p>
                    </div>
                </div>
            </div>
            <input type="hidden" name="email" value="{{ $lastLoginUser->email }}">
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        @else
            <div>
                <x-input-label for="email" :value="__('messages.email')" />
                <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>
        @endif

        <div>
            <x-input-label for="password" :value="__('messages.password')" />
            <x-text-input id="password" class="mt-1 block w-full" type="password" name="password" required autocomplete="current-password" autofocus />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <label for="remember_me" class="inline-flex items-center gap-2 text-sm text-slate-700">
            <input id="remember_me" type="checkbox" class="checkbox" name="remember">
            <span>{{ __('messages.remember_me') }}</span>
        </label>

        <div class="flex items-center justify-between gap-2">
            @if (Route::has('password.request'))
                <a class="text-sm text-slate-600 underline-offset-4 hover:text-primary hover:underline" href="{{ route('password.request') }}">
                    {{ __('messages.forgot_password') }}
                </a>
            @endif

            <x-primary-button>
                {{ __('messages.login') }}
            </x-primary-button>
        </div>

        @if (!empty($lastLoginUser))
            <div>
                <a href="{{ route('login', ['switch_user' => 1]) }}" class="btn btn-outline btn-sm w-full sm:w-auto">
                    {{ __('messages.switch_user') }}
                </a>
            </div>
        @endif
    </form>
</x-guest-layout>
