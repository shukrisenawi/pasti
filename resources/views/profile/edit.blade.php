<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.24em] text-primary">Account</p>
            <h2 class="mt-1 text-2xl font-extrabold leading-tight text-slate-900">
            {{ __('Profile') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-4">
        <div class="mx-auto max-w-7xl space-y-6">
            <div class="card border-primary/10 bg-white/95">
                <div class="card-body p-4 sm:p-8">
                    <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                    </div>
                </div>
            </div>

            <div class="card border-primary/10 bg-white/95">
                <div class="card-body p-4 sm:p-8">
                    <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                    </div>
                </div>
            </div>


        </div>
    </div>
</x-app-layout>
