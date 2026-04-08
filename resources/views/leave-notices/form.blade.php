<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-bold">
            {{ ($leaveNotice?->exists ?? false) ? __('messages.edit') : __('messages.new') }} {{ __('messages.leave_notice') }}
        </h2>
    </x-slot>

    <div class="card">
        <form method="POST" action="{{ $formAction ?? route('leave-notices.store') }}" class="space-y-4" enctype="multipart/form-data">
            @csrf
            @if(($formMethod ?? 'POST') !== 'POST')
                @method($formMethod)
            @endif

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="label-base">{{ __('messages.leave_date') }}</label>
                    <input id="leave_date" class="input-base" type="date" name="leave_date" value="{{ old('leave_date', optional($leaveNotice?->leave_date)->toDateString() ?? now()->toDateString()) }}" required>
                </div>
                <div>
                    <label class="label-base">{{ __('messages.leave_until') }}</label>
                    <input id="leave_until" class="input-base" type="date" name="leave_until" value="{{ old('leave_until', optional($leaveNotice?->leave_until)->toDateString() ?? old('leave_date', now()->toDateString())) }}" required>
                </div>
                <div>
                    <label class="label-base">{{ __('messages.mc_attachment') }} (optional)</label>
                    <input class="file-input w-full" type="file" name="mc_image" accept=".jpg,.jpeg,.png,.webp,image/*">
                </div>
                @if(($leaveNotice?->exists ?? false) && $leaveNotice?->mc_image_url)
                    <div class="flex items-end">
                        <label class="flex items-center gap-2 text-sm text-slate-600">
                            <input type="checkbox" name="remove_mc_image" value="1" class="checkbox checkbox-sm" @checked(old('remove_mc_image'))>
                            Buang lampiran MC semasa
                        </label>
                    </div>
                @endif
                <div class="md:col-span-2">
                    <label class="label-base">{{ __('messages.reason') }}</label>
                    <textarea class="input-base" name="reason" rows="4" required>{{ old('reason', $leaveNotice?->reason) }}</textarea>
                </div>
            </div>

            <div class="flex gap-2">
                <button class="btn btn-primary">{{ __('messages.save') }}</button>
                <a href="{{ route('leave-notices.index') }}" class="btn btn-outline">{{ __('messages.cancel') }}</a>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const leaveDateInput = document.getElementById('leave_date');
            const leaveUntilInput = document.getElementById('leave_until');

            if (!leaveDateInput || !leaveUntilInput) {
                return;
            }

            let previousLeaveDate = leaveDateInput.value;

            if (!leaveUntilInput.value) {
                leaveUntilInput.value = leaveDateInput.value;
            }

            leaveDateInput.addEventListener('change', () => {
                if (leaveDateInput.value !== previousLeaveDate) {
                    leaveUntilInput.value = leaveDateInput.value;
                    previousLeaveDate = leaveDateInput.value;
                }
            });
        });
    </script>
</x-app-layout>
