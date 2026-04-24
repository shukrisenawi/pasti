<x-app-layout>
    <div x-data="{ tab: '{{ $defaultTab }}' }">
        <x-slot name="header">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.24em] text-primary">Account</p>
                    <h2 class="mt-1 text-2xl font-extrabold leading-tight text-slate-900">
                    {{ __('Profile') }}
                    </h2>
                </div>

                {{-- Compact Tab Switcher in Header --}}
                <div class="flex p-1 rounded-2xl bg-slate-200/50 backdrop-blur-sm border border-slate-200 w-full sm:w-fit">
                    <button 
                        type="button" 
                        @click="tab = 'profile'" 
                        class="flex items-center justify-center gap-2 px-4 py-2 rounded-xl text-sm font-black transition-all duration-300 w-full sm:w-auto"
                        :class="tab === 'profile' ? 'bg-white text-primary shadow-md' : 'text-slate-500 hover:text-slate-700'"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                        Profil
                    </button>
                    <button 
                        type="button" 
                        @click="tab = 'password'" 
                        class="flex items-center justify-center gap-2 px-4 py-2 rounded-xl text-sm font-black transition-all duration-300 w-full sm:w-auto"
                        :class="tab === 'password' ? 'bg-white text-primary shadow-md' : 'text-slate-500 hover:text-slate-700'"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" /></svg>
                        Katakunci
                    </button>
                </div>
            </div>
        </x-slot>

        <div class="py-4">
            @php
                $isGuru = $user->hasRole('guru');
                $needsOnboarding = $isGuru && ($onboardingStatus['onboarding_completed'] ?? true) === false;
                $showProfileCard = ! $needsOnboarding || $wizardStep === 'profile';
                $showPastiCard = $needsOnboarding && $wizardStep === 'pasti';
                $showPasswordCard = ! $needsOnboarding || $wizardStep === 'password' || $errors->updatePassword->isNotEmpty() || session('status') === 'password-updated';
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
                    <div class="mt-3 flex flex-wrap items-center gap-3 text-sm">
                        <span class="inline-flex items-center rounded-full px-3 py-1 font-semibold {{ $wizardStep === 'profile' ? 'bg-primary text-white' : 'bg-emerald-100 text-emerald-800' }}">1. Kemaskini Profil</span>
                        <span class="text-slate-400">-></span>
                        <span class="inline-flex items-center rounded-full px-3 py-1 font-semibold {{ $wizardStep === 'pasti' ? 'bg-primary text-white' : (($onboardingStatus['pasti_completed'] ?? false) ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600') }}">2. Pilih PASTI</span>
                        <span class="text-slate-400">-></span>
                        <span class="inline-flex items-center rounded-full px-3 py-1 font-semibold {{ $wizardStep === 'password' ? 'bg-primary text-white' : 'bg-slate-100 text-slate-600' }}">3. Tukar Kata Laluan</span>
                    </div>
                </div>
            @endif

            <div class="mx-auto max-w-7xl">
                {{-- Tab Contents --}}
                <div class="space-y-6">
                    {{-- Profile Tab --}}
                    <div x-show="tab === 'profile'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" x-cloak>
                        <div class="card border-primary/10 bg-white shadow-xl">
                            <div class="card-body p-6 sm:p-10">
                                <div class="max-w-xl">
                                    @include('profile.partials.update-profile-information-form')
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Password Tab --}}
                    <div x-show="tab === 'password'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" x-cloak>
                        <div class="card border-primary/10 bg-white shadow-xl">
                            <div class="card-body p-6 sm:p-10">
                                <div class="max-w-xl">
                                    @include('profile.partials.update-password-form')
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
