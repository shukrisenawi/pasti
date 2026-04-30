<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold">{{ __('messages.fill_guru_salary_info') }}</h2>
        </div>
    </x-slot>

    <div class="card max-w-3xl border-primary/10 bg-white/95">
        <form method="POST" action="{{ route('guru-salary-information.update', $salaryRequest) }}" class="space-y-4">
            @csrf

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="label-base">{{ __('messages.gaji') }} <span class="text-red-600">*</span></label>
                    <input type="number" step="0.01" min="0" name="gaji" value="{{ old('gaji', $salaryRequest->gaji ?? '') }}" class="input-base" required>
                </div>
                <div>
                    <label class="label-base">{{ __('messages.elaun_transit') }} <span class="text-red-600">*</span></label>
                    <input type="number" step="0.01" min="0" name="elaun" value="{{ old('elaun', $salaryRequest->elaun ?? '') }}" class="input-base" required>
                </div>
                <div>
                    <label class="label-base">{{ __('messages.elaun_lain') }} <span class="text-red-600">*</span></label>
                    <input type="number" step="0.01" min="0" name="elaun_lain" value="{{ old('elaun_lain', $salaryRequest->elaun_lain ?? '') }}" class="input-base" required>
                </div>
            </div>

            <div class="flex gap-2">
                <button class="btn btn-primary">{{ __('messages.save') }}</button>
                <a href="{{ route('guru-salary-information.index') }}" class="btn btn-outline">{{ __('messages.cancel') }}</a>
            </div>
        </form>
    </div>
</x-app-layout>
