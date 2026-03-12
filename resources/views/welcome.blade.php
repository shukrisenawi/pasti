<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistem pengurusan pasti</title>
    <link rel="icon" type="image/png" href="{{ asset('images/pasti-logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="relative isolate overflow-hidden">
        <div class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(circle_at_top_left,_rgba(34,197,94,0.22),transparent_28rem),radial-gradient(circle_at_bottom_right,_rgba(21,128,61,0.18),transparent_30rem)]"></div>

        <header class="mx-auto flex max-w-7xl items-center justify-between px-4 py-6 sm:px-6 lg:px-8">
            <div class="flex items-center gap-3">
                <x-application-logo class="h-14 w-14 rounded-full border border-primary/20 bg-white object-contain p-1 shadow-sm" />
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.3em] text-primary">PASTI</p>
                    <p class="mt-1 text-sm text-slate-500">Portal pengurusan operasi dan kehadiran.</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                @auth
                    <a href="{{ url('/dashboard') }}" class="btn btn-outline">Dashboard</a>
                @else
                    @if (Route::has('login'))
                        <a href="{{ route('login') }}" class="btn btn-outline">Log masuk</a>
                    @endif
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="btn btn-primary">Daftar</a>
                    @endif
                @endauth
            </div>
        </header>

        <main class="mx-auto grid max-w-7xl gap-10 px-4 pb-16 pt-8 sm:px-6 lg:grid-cols-[1.05fr_0.95fr] lg:px-8 lg:pb-24 lg:pt-14">
            <section class="py-4 lg:py-10">
                <p class="text-sm font-bold uppercase tracking-[0.3em] text-primary">Sistem Hijau PASTI</p>
                <h1 class="mt-5 max-w-3xl text-4xl font-extrabold leading-tight text-slate-900 sm:text-5xl lg:text-6xl">Reka bentuk lebih profesional untuk urus guru, kelas, KPI dan program tanpa kekusutan.</h1>
                <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-600">Portal ini menyatukan data PASTI dalam antaramuka yang jelas, moden dan fokus kepada aliran kerja sebenar pentadbiran harian.</p>

                <div class="mt-8 flex flex-wrap gap-3">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="btn btn-primary">Buka Dashboard</a>
                    @else
                        @if (Route::has('login'))
                            <a href="{{ route('login') }}" class="btn btn-primary">Masuk Sekarang</a>
                        @endif
                        <a href="#features" class="btn btn-outline">Lihat Ciri</a>
                    @endauth
                </div>

                <div id="features" class="mt-12 grid gap-4 sm:grid-cols-3">
                    <div class="card bg-white/90">
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-primary">Guru</p>
                        <p class="mt-3 text-lg font-bold text-slate-900">Pengurusan akaun dan profil lebih kemas.</p>
                    </div>
                    <div class="card bg-white/90">
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-primary">Program</p>
                        <p class="mt-3 text-lg font-bold text-slate-900">Kehadiran, status dan notis lebih cepat disemak.</p>
                    </div>
                    <div class="card bg-white/90">
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-primary">KPI</p>
                        <p class="mt-3 text-lg font-bold text-slate-900">Prestasi guru boleh dipantau secara lebih teratur.</p>
                    </div>
                </div>
            </section>

            <section class="relative">
                <div class="card overflow-hidden border-primary/10 bg-white/90 p-0">
                    <div class="border-b border-slate-200 bg-slate-50 px-6 py-4">
                        <p class="text-xs font-bold uppercase tracking-[0.22em] text-slate-500">Preview</p>
                        <h2 class="mt-2 text-2xl font-extrabold text-slate-900">Antaramuka pentadbiran yang lebih tersusun</h2>
                    </div>
                    <div class="space-y-4 p-6">
                        <div class="rounded-3xl bg-gradient-to-r from-primary to-primary-dark p-5 text-white">
                            <p class="text-xs uppercase tracking-[0.2em] text-white/70">Dashboard</p>
                            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                <div class="rounded-2xl bg-white/10 p-4">
                                    <p class="text-xs text-white/70">Program Terkini</p>
                                    <p class="mt-2 text-xl font-bold">Dipaparkan jelas</p>
                                </div>
                                <div class="rounded-2xl bg-white/10 p-4">
                                    <p class="text-xs text-white/70">Status Guru</p>
                                    <p class="mt-2 text-xl font-bold">Boleh dikemas kini</p>
                                </div>
                            </div>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Navigasi</p>
                                <ul class="mt-3 space-y-2 text-sm font-semibold text-slate-700">
                                    <li>Dashboard</li>
                                    <li>Guru</li>
                                    <li>Kawasan</li>
                                    <li>Program</li>
                                </ul>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Tema</p>
                                <div class="mt-3 flex gap-2">
                                    <span class="h-10 w-10 rounded-2xl bg-emerald-200"></span>
                                    <span class="h-10 w-10 rounded-2xl bg-emerald-500"></span>
                                    <span class="h-10 w-10 rounded-2xl bg-emerald-800"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
