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
        @php
            $isGuru = $user->hasRole('guru');
            $needsOnboarding = $isGuru && ($onboardingStatus['onboarding_completed'] ?? true) === false;
            $showProfileCard = ! $needsOnboarding || $wizardStep === 'profile';
            $showPasswordCard = ! $needsOnboarding || $wizardStep === 'password' || $errors->updatePassword->isNotEmpty() || session('status') === 'password-updated';
            $defaultTab = $errors->updatePassword->isNotEmpty() ? 'password' : 'profile';
        @endphp

        @if(session('onboarding_notice'))
            <div class="mx-auto mb-4 max-w-7xl rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                {{ session('onboarding_notice') }}
            </div>
        @endif

        @if(session('wizard_notice'))
            <div class="mx-auto mb-4 max-w-7xl rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                {{ session('wizard_notice') }}
            </div>
        @endif

        @if($isGuru && $needsOnboarding)
            <div class="mx-auto mb-4 max-w-7xl rounded-2xl border border-slate-200 bg-white px-4 py-4">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Wizard Onboarding Guru</p>
                <div class="mt-3 flex items-center gap-3 text-sm">
                    <span class="inline-flex items-center rounded-full px-3 py-1 font-semibold {{ $wizardStep === 'profile' ? 'bg-primary text-white' : 'bg-emerald-100 text-emerald-800' }}">1. Kemaskini Profil</span>
                    <span class="text-slate-400">-></span>
                    <span class="inline-flex items-center rounded-full px-3 py-1 font-semibold {{ $wizardStep === 'password' ? 'bg-primary text-white' : 'bg-slate-100 text-slate-600' }}">2. Tukar Kata Laluan</span>
                </div>
            </div>
        @endif

        <div class="mx-auto max-w-7xl space-y-6" x-data="{ tab: '{{ $defaultTab }}' }">
            @if($isGuru && ! $needsOnboarding)
                <div class="rounded-2xl border border-slate-200 bg-white p-2">
                    <button type="button" @click="tab='profile'" class="rounded-xl px-4 py-2 text-sm font-semibold" :class="tab==='profile' ? 'bg-primary text-white' : 'text-slate-600 hover:bg-slate-100'">Kemaskini Profil</button>
                    <button type="button" @click="tab='password'" class="rounded-xl px-4 py-2 text-sm font-semibold" :class="tab==='password' ? 'bg-primary text-white' : 'text-slate-600 hover:bg-slate-100'">Tukar Kata Laluan</button>
                </div>
            @endif

            @if($showProfileCard)
            <div class="card border-primary/10 bg-white/95" x-show="{{ $isGuru && ! $needsOnboarding ? "tab==='profile'" : 'true' }}" x-cloak>
                <div class="card-body p-4 sm:p-8">
                    <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                    </div>
                </div>
            </div>
            @endif

            @if($showPasswordCard)
            <div id="password-step" class="card border-primary/10 bg-white/95" x-show="{{ $isGuru && ! $needsOnboarding ? "tab==='password'" : 'true' }}" x-cloak>
                <div class="card-body p-4 sm:p-8">
                    <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
