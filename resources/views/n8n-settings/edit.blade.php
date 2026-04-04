<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold">Setting n8n</h2>
        </div>
    </x-slot>

    <section class="card space-y-4">
        <h3 class="text-base font-bold text-slate-900">Tetapan Webhook WhatsApp</h3>

        <form method="POST" action="{{ route('n8n-settings.update') }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <p class="label-base">Mode Hantar Webhook <span class="text-rose-600">*</span></p>
                <div class="mt-2 grid gap-2 sm:grid-cols-2">
                    <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2">
                        <input type="radio" name="webhook_mode" value="test" class="h-4 w-4 text-primary focus:ring-primary" @checked(old('webhook_mode', $settings['webhook_mode'] ?? 'production') === 'test')>
                        <span class="text-sm font-semibold text-slate-700">Test</span>
                    </label>
                    <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2">
                        <input type="radio" name="webhook_mode" value="production" class="h-4 w-4 text-primary focus:ring-primary" @checked(old('webhook_mode', $settings['webhook_mode'] ?? 'production') === 'production')>
                        <span class="text-sm font-semibold text-slate-700">Production</span>
                    </label>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label for="webhook_url_test" class="label-base">Webhook Group Guru (Test) <span class="text-rose-600">*</span></label>
                    <input id="webhook_url_test" name="webhook_url_test" type="url" required class="input-base" value="{{ old('webhook_url_test', $settings['webhook_url_test'] ?? '') }}">
                </div>
                <div>
                    <label for="webhook_url_production" class="label-base">Webhook Group Guru (Production) <span class="text-rose-600">*</span></label>
                    <input id="webhook_url_production" name="webhook_url_production" type="url" required class="input-base" value="{{ old('webhook_url_production', $settings['webhook_url_production'] ?? '') }}">
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label for="webhook_url_group2_test" class="label-base">Webhook Group AJK (Test) <span class="text-rose-600">*</span></label>
                    <input id="webhook_url_group2_test" name="webhook_url_group2_test" type="url" required class="input-base" value="{{ old('webhook_url_group2_test', $settings['webhook_url_group2_test'] ?? '') }}">
                </div>
                <div>
                    <label for="webhook_url_group2_production" class="label-base">Webhook Group AJK (Production) <span class="text-rose-600">*</span></label>
                    <input id="webhook_url_group2_production" name="webhook_url_group2_production" type="url" required class="input-base" value="{{ old('webhook_url_group2_production', $settings['webhook_url_group2_production'] ?? '') }}">
                </div>
            </div>

            <div>
                <label for="text_program_created" class="label-base">Teks Program Baru <span class="text-rose-600">*</span></label>
                <textarea id="text_program_created" name="text_program_created" rows="3" required class="input-base">{{ old('text_program_created', $settings['text_program_created'] ?? '') }}</textarea>
                <p class="mt-1 text-xs text-slate-500">Placeholder: <code>{tajuk}</code>, <code>{tarikh}</code>, <code>{hari}</code>, <code>{masa}</code>, <code>{lokasi}</code></p>
            </div>

            <div>
                <label for="text_salary_request" class="label-base">Teks Permintaan Gaji Guru <span class="text-rose-600">*</span></label>
                <textarea id="text_salary_request" name="text_salary_request" rows="3" required class="input-base">{{ old('text_salary_request', $settings['text_salary_request'] ?? '') }}</textarea>
                <p class="mt-1 text-xs text-slate-500">Placeholder: <code>{tarikh}</code></p>
            </div>

            <div>
                <label for="text_pasti_info_request" class="label-base">Teks Permintaan Maklumat PASTI <span class="text-rose-600">*</span></label>
                <textarea id="text_pasti_info_request" name="text_pasti_info_request" rows="3" required class="input-base">{{ old('text_pasti_info_request', $settings['text_pasti_info_request'] ?? '') }}</textarea>
                <p class="mt-1 text-xs text-slate-500">Placeholder: <code>{tarikh}</code></p>
            </div>

            <div>
                <label for="text_guru_course_offer" class="label-base">Teks Permintaan Kursus Guru <span class="text-rose-600">*</span></label>
                <textarea id="text_guru_course_offer" name="text_guru_course_offer" rows="3" required class="input-base">{{ old('text_guru_course_offer', $settings['text_guru_course_offer'] ?? '') }}</textarea>
                <p class="mt-1 text-xs text-slate-500">Placeholder: <code>{semester}</code>, <code>{tarikh_akhir}</code>, <code>{nota}</code></p>
            </div>

            <div>
                <label for="text_leave_notice_submitted" class="label-base">Teks Group 2 - Notis Cuti <span class="text-rose-600">*</span></label>
                <textarea id="text_leave_notice_submitted" name="text_leave_notice_submitted" rows="3" required class="input-base">{{ old('text_leave_notice_submitted', $settings['text_leave_notice_submitted'] ?? '') }}</textarea>
                <p class="mt-1 text-xs text-slate-500">Placeholder: <code>{nama_guru}</code>, <code>{tarikh_cuti}</code>, <code>{tarikh_hingga}</code>, <code>{sebab}</code></p>
            </div>

            <div>
                <label for="text_claim_submitted" class="label-base">Teks Group 2 - Claim Guru <span class="text-rose-600">*</span></label>
                <textarea id="text_claim_submitted" name="text_claim_submitted" rows="3" required class="input-base">{{ old('text_claim_submitted', $settings['text_claim_submitted'] ?? '') }}</textarea>
                <p class="mt-1 text-xs text-slate-500">Placeholder: <code>{nama_guru}</code>, <code>{jumlah}</code>, <code>{tarikh_claim}</code>, <code>{catatan}</code></p>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </section>
</x-app-layout>
