<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-bold">{{ __('messages.kursus_guru') }}</h2>
    </x-slot>

    @if(session('kursus_guru_warning'))
        <div class="alert alert-warning mb-4">{{ session('kursus_guru_warning') }}</div>
    @endif

    @if($canSendOffer)
        <section class="card mb-5">
            <h3 class="text-base font-bold text-slate-900">{{ __('messages.kursus_guru') }} - Admin</h3>
            <p class="mt-1 text-sm text-slate-500">Isi tarikh akhir untuk semester yang mahu dihantar. Sistem hanya hantar semester yang ada tarikh.</p>

            <form method="POST" action="{{ route('kursus-guru.offers.send') }}" class="mt-4 space-y-4">
                @csrf

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach($semesterList as $semester)
                        @php
                            $latest = $latestOffers->get($semester);
                            $isFirstSemester = $semester === 1;
                            $isLocked = $latest && $latest->responses_count > 0 && $latest->responded_count < $latest->responses_count;
                            $deadlineValue = $isLocked
                                ? $latest->registration_deadline?->format('Y-m-d')
                                : old('deadlines.' . $semester, '');
                            $noteValue = $isLocked
                                ? ($latest->note ?? '')
                                : old('notes.' . $semester, '');
                        @endphp

                        <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p class="text-sm font-extrabold text-slate-800">Semester {{ $semester }}</p>
                                    <p class="text-xs text-slate-500">{{ $isFirstSemester ? 'Permulaan' : ('Sasaran: Guru Semester ' . ($semester - 1)) }}</p>
                                </div>
                                @if($isLocked)
                                    <span class="rounded-full bg-amber-100 px-2 py-1 text-[10px] font-bold text-amber-700">Menunggu {{ $latest->responded_count }}/{{ $latest->responses_count }}</span>
                                @endif
                            </div>

                            <div class="mt-3 space-y-2">
                                <label class="label-base">Tarikh Akhir Pendaftaran</label>
                                <input
                                    type="date"
                                    name="deadlines[{{ $semester }}]"
                                    class="input-base input-sm"
                                    value="{{ $deadlineValue }}"
                                    @disabled($isLocked)
                                >

                                <label class="label-base">Nota</label>
                                <textarea
                                    name="notes[{{ $semester }}]"
                                    class="input-base min-h-[88px]"
                                    placeholder="Nota kepada guru semester ini"
                                    @disabled($isLocked)
                                >{{ $noteValue }}</textarea>
                            </div>

                            <div class="mt-3 text-xs text-slate-600 space-y-0.5">
                                @if($latest)
                                    <p>Dihantar: {{ $latest->sent_at?->format('d/m/Y H:i') }}</p>
                                    <p>Jawapan: {{ $latest->responded_count }}/{{ $latest->responses_count }}</p>
                                    <p>Sambung: {{ $latest->continue_count }} | Tidak sambung: {{ $latest->stop_count }}</p>
                                @else
                                    <p>Belum pernah dihantar.</p>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>

                <div>
                    <button class="btn btn-primary">Hantar</button>
                </div>
            </form>
        </section>
    @endif

    @role('guru')
        <section class="card mb-5">
            <h3 class="text-base font-bold text-slate-900">Permintaan Sambung Kursus</h3>
            <p class="mt-1 text-sm text-slate-500">Jawab sebelum tarikh akhir untuk setiap semester yang ditawarkan.</p>

            @if($pendingResponses->count())
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    @foreach($pendingResponses as $response)
                        @php
                            $targetSemester = (int) $response->offer->target_semester;
                            $sourceSemester = max(1, $targetSemester - 1);
                        @endphp
                        <article class="rounded-2xl border border-amber-200 bg-amber-50 p-4">
                            <p class="text-sm font-bold text-slate-800">Semester {{ $sourceSemester }} -> Semester {{ $targetSemester }}</p>
                            <p class="mt-1 text-xs text-slate-600">Tarikh akhir: {{ $response->offer->registration_deadline?->format('d/m/Y') }}</p>
                            @if($response->offer->note)
                                <p class="mt-1 text-xs text-slate-600">Nota: {{ $response->offer->note }}</p>
                            @endif

                            <form method="POST" action="{{ route('kursus-guru.responses.respond', $response) }}" class="mt-3 space-y-2">
                                @csrf
                                <select name="decision" class="input-base input-sm" required>
                                    <option value="">-- Pilih --</option>
                                    <option value="continue">Saya mahu sambung</option>
                                    <option value="stop">Saya tidak mahu sambung</option>
                                </select>
                                <textarea name="stop_reason" class="input-base min-h-[92px]" placeholder="Jika tidak sambung, nyatakan alasan"></textarea>
                                <button class="btn btn-primary btn-sm w-full">{{ __('messages.save') }}</button>
                            </form>
                        </article>
                    @endforeach
                </div>
            @else
                <p class="mt-4 text-sm text-slate-500">Tiada permintaan sambung semester buat masa ini.</p>
            @endif
        </section>

        <section class="card">
            <h3 class="text-base font-bold text-slate-900">Sejarah Jawapan</h3>
            @if($historyResponses->count())
                <div class="mt-4 space-y-2">
                    @foreach($historyResponses as $response)
                        @php
                            $targetSemester = (int) $response->offer->target_semester;
                            $sourceSemester = max(1, $targetSemester - 1);
                        @endphp
                        <div class="rounded-xl border border-slate-200 p-3 text-sm">
                            <p class="font-semibold text-slate-800">Semester {{ $sourceSemester }} -> Semester {{ $targetSemester }}</p>
                            <p class="text-slate-600">Jawapan: {{ $response->decision === 'continue' ? 'Sambung' : 'Tidak sambung' }}</p>
                            @if($response->decision === 'stop' && $response->stop_reason)
                                <p class="text-slate-600">Alasan: {{ $response->stop_reason }}</p>
                            @endif
                            <p class="text-xs text-slate-500">Dijawab: {{ $response->responded_at?->format('d/m/Y H:i') }}</p>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="mt-4 text-sm text-slate-500">Belum ada jawapan.</p>
            @endif
        </section>
    @endrole
</x-app-layout>
