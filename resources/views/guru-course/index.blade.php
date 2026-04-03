<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-bold">{{ __('messages.kursus_guru') }}</h2>
    </x-slot>

    @if($canSendOffer)
        <section class="card mb-5">
            <h3 class="text-base font-bold text-slate-900">{{ __('messages.kursus_guru') }} - Admin</h3>
            <p class="mt-1 text-sm text-slate-500">Tetapkan tarikh akhir pendaftaran dan hantar notifikasi sambung semester.</p>

            <div class="mt-4 overflow-x-auto">
                <table class="table-base">
                    <thead>
                    <tr>
                        <th>Semester</th>
                        <th>Sasaran Notifikasi</th>
                        <th>Tarikh Akhir Pendaftaran</th>
                        <th>Ringkasan Terakhir</th>
                        <th>{{ __('messages.actions') }}</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                    @foreach($semesterList as $semester)
                        @php
                            $latest = $latestOffers->get($semester);
                            $isFirstSemester = $semester === 1;
                        @endphp
                        <tr>
                            <td class="font-semibold">Semester {{ $semester }}</td>
                            <td>{{ $isFirstSemester ? '-' : 'Guru Semester ' . ($semester - 1) }}</td>
                            <td>
                                @if($isFirstSemester)
                                    -
                                @else
                                    <form method="POST" action="{{ route('kursus-guru.offers.send') }}" class="flex flex-wrap items-center gap-2">
                                        @csrf
                                        <input type="hidden" name="target_semester" value="{{ $semester }}">
                                        <input
                                            type="date"
                                            name="registration_deadline"
                                            class="input-base input-sm"
                                            value="{{ old('registration_deadline') }}"
                                            required
                                        >
                                        <button class="btn btn-primary btn-sm">Hantar</button>
                                    </form>
                                @endif
                            </td>
                            <td>
                                @if($latest)
                                    <div class="text-xs text-slate-600 space-y-0.5">
                                        <p>Dihantar: {{ $latest->sent_at?->format('d/m/Y H:i') }}</p>
                                        <p>Tarikh akhir: {{ $latest->registration_deadline?->format('d/m/Y') }}</p>
                                        <p>Jawapan: {{ $latest->responded_count }}/{{ $latest->responses_count }}</p>
                                        <p>Sambung: {{ $latest->continue_count }} | Tidak sambung: {{ $latest->stop_count }}</p>
                                    </div>
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                @if($isFirstSemester)
                                    <span class="text-xs text-slate-400">Tiada semester sebelumnya</span>
                                @else
                                    <span class="text-xs text-slate-500">Notifikasi hantar ke guru Semester {{ $semester - 1 }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
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
