<nav x-data="{ open: false }" class="border-b border-base-300 bg-base-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-9 w-auto object-contain" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>

                    @unless(auth()->user()->hasRole('guru') && !auth()->user()->hasAnyRole(['master_admin', 'admin']))
                        <x-nav-link :href="route('claims.index')" :active="request()->routeIs('claims.*')">
                            {{ __('Claim') }}
                            @php($pendingClaims = auth()->user()->pending_claims_count)
                            @if($pendingClaims > 0)
                                <span class="ms-2 badge badge-error badge-sm text-white">{{ $pendingClaims }}</span>
                            @endif
                        </x-nav-link>
                    @endunless

                    @if(auth()->user()->hasAnyRole(['master_admin', 'admin']))
                        <x-dropdown align="left" width="48">
                            <x-slot name="trigger">
                                <button class="btn btn-sm btn-ghost gap-1 {{ request()->routeIs(['users.*', 'kawasan.*', 'pasti.*', 'kelas.*']) ? 'bg-base-200' : '' }}">
                                    <span>{{ __('Pengurusan') }}</span>
                                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" /></svg>
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                <x-dropdown-link :href="route('kawasan.index')">{{ __('Kawasan') }}</x-dropdown-link>
                                <x-dropdown-link :href="route('pasti.index')">{{ __('PASTI') }}</x-dropdown-link>
                                <x-dropdown-link :href="route('kelas.index')">{{ __('Kelas') }}</x-dropdown-link>
                                <div class="divider my-0"></div>
                                <x-dropdown-link :href="route('users.gurus.index')">{{ __('Guru') }}</x-dropdown-link>
                                @if(auth()->user()->hasRole('master_admin'))
                                    <x-dropdown-link :href="route('users.admins.index')">{{ __('Admin') }}</x-dropdown-link>
                                @endif
                            </x-slot>
                        </x-dropdown>

                        <x-dropdown align="left" width="48">
                            <x-slot name="trigger">
                                <button class="btn btn-sm btn-ghost gap-1 {{ request()->routeIs(['kpi.*', 'financial.index', 'messages.index', 'programs.index', 'leave-notices.index']) ? 'bg-base-200' : '' }}">
                                    <span>{{ __('Laporan/Aktiviti') }}</span>
                                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" /></svg>
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                <x-dropdown-link :href="route('kpi.gurus.index')">{{ __('KPI Guru') }}</x-dropdown-link>
                                <x-dropdown-link :href="route('financial.index')">{{ __('Kewangan') }}</x-dropdown-link>
                                <x-dropdown-link :href="route('programs.index')">{{ __('Program') }}</x-dropdown-link>
                                <x-dropdown-link :href="route('leave-notices.index')">{{ __('Notis Cuti') }}</x-dropdown-link>
                                <x-dropdown-link :href="route('messages.index')">{{ __('Mesej') }}</x-dropdown-link>
                            </x-slot>
                        </x-dropdown>
                    @endif
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="btn btn-ghost btn-sm">
                            <div>{{ Auth::user()->display_name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="btn btn-ghost btn-square btn-sm">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>

            @unless(auth()->user()->hasRole('guru') && !auth()->user()->hasAnyRole(['master_admin', 'admin']))
                <x-responsive-nav-link :href="route('claims.index')" :active="request()->routeIs('claims.*')">
                    <div class="flex items-center justify-between w-full">
                        <span>{{ __('Claim') }}</span>
                        @php($pendingClaims = auth()->user()->pending_claims_count)
                        @if($pendingClaims > 0)
                            <span class="badge badge-error badge-sm text-white">{{ $pendingClaims }}</span>
                        @endif
                    </div>
                </x-responsive-nav-link>
            @endunless

            @if(auth()->user()->hasAnyRole(['master_admin', 'admin']))
                <div x-data="{ open: false }" class="space-y-1">
                    <button @click="open = !open" class="btn btn-sm btn-ghost w-full justify-between {{ request()->routeIs(['users.*', 'kawasan.*', 'pasti.*', 'kelas.*']) ? 'bg-base-200' : '' }}">
                        <span>{{ __('Pengurusan') }}</span>
                        <svg class="h-4 w-4 transform transition-transform" :class="open ? 'rotate-180' : ''" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" /></svg>
                    </button>
                    <div x-show="open" class="ps-4 space-y-1" style="display: none;">
                        <x-responsive-nav-link :href="route('kawasan.index')" :active="request()->routeIs('kawasan.*')">{{ __('Kawasan') }}</x-responsive-nav-link>
                        <x-responsive-nav-link :href="route('pasti.index')" :active="request()->routeIs('pasti.index')">{{ __('PASTI') }}</x-responsive-nav-link>
                        <x-responsive-nav-link :href="route('kelas.index')" :active="request()->routeIs('kelas.*')">{{ __('Kelas') }}</x-responsive-nav-link>
                        <x-responsive-nav-link :href="route('users.gurus.index')" :active="request()->routeIs('users.gurus.*')">{{ __('Guru') }}</x-responsive-nav-link>
                        @if(auth()->user()->hasRole('master_admin'))
                            <x-responsive-nav-link :href="route('users.admins.index')" :active="request()->routeIs('users.admins.*')">{{ __('Admin') }}</x-responsive-nav-link>
                        @endif
                    </div>
                </div>

                <div x-data="{ open: false }" class="space-y-1">
                    <button @click="open = !open" class="btn btn-sm btn-ghost w-full justify-between {{ request()->routeIs(['kpi.*', 'financial.index', 'messages.index', 'programs.index', 'leave-notices.index']) ? 'bg-base-200' : '' }}">
                        <span>{{ __('Laporan/Aktiviti') }}</span>
                        <svg class="h-4 w-4 transform transition-transform" :class="open ? 'rotate-180' : ''" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" /></svg>
                    </button>
                    <div x-show="open" class="ps-4 space-y-1" style="display: none;">
                        <x-responsive-nav-link :href="route('kpi.gurus.index')" :active="request()->routeIs('kpi.gurus.*')">{{ __('KPI Guru') }}</x-responsive-nav-link>
                        <x-responsive-nav-link :href="route('financial.index')" :active="request()->routeIs('financial.*')">{{ __('Kewangan') }}</x-responsive-nav-link>
                        <x-responsive-nav-link :href="route('programs.index')" :active="request()->routeIs('programs.index')">{{ __('Program') }}</x-responsive-nav-link>
                        <x-responsive-nav-link :href="route('leave-notices.index')" :active="request()->routeIs('leave-notices.*')">{{ __('Notis Cuti') }}</x-responsive-nav-link>
                        <x-responsive-nav-link :href="route('messages.index')" :active="request()->routeIs('messages.*')">{{ __('Mesej') }}</x-responsive-nav-link>
                    </div>
                </div>
            @endif
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-base-300">
            <div class="px-4">
                <div class="font-medium text-base text-base-content">{{ Auth::user()->display_name }}</div>
                <div class="font-medium text-sm text-base-content opacity-70">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
