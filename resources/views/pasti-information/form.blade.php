<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold">{{ __('messages.fill_pasti_info') }} - {{ $infoRequest->pasti?->name }}</h2>
        </div>
    </x-slot>

    <div class="card max-w-4xl border-primary/10 bg-white/95">
        <form method="POST" action="{{ route('pasti-information.update', $infoRequest) }}" class="space-y-4">
            @csrf

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="label-base">{{ __('messages.total_guru') }}</label>
                    <input type="number" min="0" name="jumlah_guru" value="{{ old('jumlah_guru', $infoRequest->jumlah_guru ?? 0) }}" class="input-base" required>
                </div>
                <div>
                    <label class="label-base">{{ __('messages.total_assistant_teacher') }}</label>
                    <input type="number" min="0" name="jumlah_pembantu_guru" value="{{ old('jumlah_pembantu_guru', $infoRequest->jumlah_pembantu_guru ?? 0) }}" class="input-base" required>
                </div>
                <div>
                    <label class="label-base">{{ __('messages.male_students_age_4') }}</label>
                    <input type="number" min="0" name="murid_lelaki_4_tahun" value="{{ old('murid_lelaki_4_tahun', $infoRequest->murid_lelaki_4_tahun ?? 0) }}" class="input-base" required>
                </div>
                <div>
                    <label class="label-base">{{ __('messages.female_students_age_4') }}</label>
                    <input type="number" min="0" name="murid_perempuan_4_tahun" value="{{ old('murid_perempuan_4_tahun', $infoRequest->murid_perempuan_4_tahun ?? 0) }}" class="input-base" required>
                </div>
                <div>
                    <label class="label-base">{{ __('messages.male_students_age_5') }}</label>
                    <input type="number" min="0" name="murid_lelaki_5_tahun" value="{{ old('murid_lelaki_5_tahun', $infoRequest->murid_lelaki_5_tahun ?? 0) }}" class="input-base" required>
                </div>
                <div>
                    <label class="label-base">{{ __('messages.female_students_age_5') }}</label>
                    <input type="number" min="0" name="murid_perempuan_5_tahun" value="{{ old('murid_perempuan_5_tahun', $infoRequest->murid_perempuan_5_tahun ?? 0) }}" class="input-base" required>
                </div>
                <div>
                    <label class="label-base">{{ __('messages.male_students_age_6') }}</label>
                    <input type="number" min="0" name="murid_lelaki_6_tahun" value="{{ old('murid_lelaki_6_tahun', $infoRequest->murid_lelaki_6_tahun ?? 0) }}" class="input-base" required>
                </div>
                <div>
                    <label class="label-base">{{ __('messages.female_students_age_6') }}</label>
                    <input type="number" min="0" name="murid_perempuan_6_tahun" value="{{ old('murid_perempuan_6_tahun', $infoRequest->murid_perempuan_6_tahun ?? 0) }}" class="input-base" required>
                </div>
            </div>

            <div class="flex gap-2">
                <button class="btn btn-primary">{{ __('messages.save') }}</button>
                <a href="{{ route('pasti-information.index') }}" class="btn btn-outline">{{ __('messages.cancel') }}</a>
            </div>
        </form>
    </div>
</x-app-layout>
