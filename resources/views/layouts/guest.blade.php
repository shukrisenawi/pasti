<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sistem pengurusan pasti</title>
    <link rel="icon" type="image/png" href="{{ asset('images/pasti-logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="panel-shell flex min-h-screen items-center justify-center px-4 py-10">
    <div class="grid w-full max-w-6xl overflow-hidden rounded-[2rem] border border-white/70 bg-white/80 shadow-2xl backdrop-blur xl:grid-cols-[1.05fr_0.95fr]">
        <div class="relative hidden overflow-hidden bg-gradient-to-br from-primary via-primary-dark to-emerald-900 p-10 text-primary-content xl:block">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,_rgba(255,255,255,0.18),transparent_30rem)]"></div>
            <div class="relative flex h-full flex-col justify-between">
                <div>
                    <h1 class="max-w-md text-4xl font-extrabold leading-tight">Pengurusan guru, program, kelas dan kehadiran dalam satu portal hijau yang kemas.</h1>
                    <p class="mt-5 max-w-lg text-base leading-7 text-white/78">Direka untuk operasi harian PASTI dengan aliran kerja lebih jelas, lebih cepat dan lebih profesional.</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="rounded-2xl border border-white/15 bg-white/10 p-4">
                        <p class="text-xs uppercase tracking-[0.2em] text-white/60">Program</p>
                        <p class="mt-2 text-2xl font-bold">Tersusun</p>
                    </div>
                    <div class="rounded-2xl border border-white/15 bg-white/10 p-4">
                        <p class="text-xs uppercase tracking-[0.2em] text-white/60">Kehadiran</p>
                        <p class="mt-2 text-2xl font-bold">Live</p>
                    </div>
                    <div class="rounded-2xl border border-white/15 bg-white/10 p-4">
                        <p class="text-xs uppercase tracking-[0.2em] text-white/60">KPI</p>
                        <p class="mt-2 text-2xl font-bold">Terukur</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="p-6 sm:p-10 xl:p-12">
            <div class="mx-auto w-full max-w-md">
                <div class="flex items-center gap-3">
                    <x-application-logo class="h-12 w-12 rounded-full border border-primary/20 bg-white object-contain p-1" />
                    <p class="text-xs font-bold uppercase tracking-[0.28em] text-primary">PASTI Portal</p>
                </div>
                <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-slate-900">{{ __('messages.login_subtitle') }}</h2>
                <p class="mt-2 text-sm leading-6 text-slate-500">Sila teruskan untuk akses papan pemuka, pengurusan data dan rekod semasa.</p>
                <div class="mt-8">{{ $slot }}</div>
            </div>
        </div>
    </div>
</div>
</body>
</html>

