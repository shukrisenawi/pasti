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
                <label for="webhook_url" class="label-base">Webhook URL <span class="text-rose-600">*</span></label>
                <input id="webhook_url" name="webhook_url" type="url" required class="input-base" value="{{ old('webhook_url', $settings['webhook_url'] ?? '') }}">
            </div>
            <div>
                <label for="webhook_url_group2" class="label-base">Webhook URL Group 2 <span class="text-rose-600">*</span></label>
                <input id="webhook_url_group2" name="webhook_url_group2" type="url" required class="input-base" value="{{ old('webhook_url_group2', $settings['webhook_url_group2'] ?? '') }}">
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
