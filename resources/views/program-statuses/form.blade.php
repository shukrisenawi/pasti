<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-bold">{{ $status->exists ? __('messages.edit') : __('messages.new') }} {{ __('messages.program_statuses') }}</h2>
    </x-slot>

    <div class="card">
        <form method="POST" action="{{ $status->exists ? route('program-statuses.update', $status) : route('program-statuses.store') }}" class="space-y-4">
            @csrf
            @if($status->exists)
                @method('PUT')
            @endif

            <div>
                <label class="label-base">{{ __('messages.name') }}</label>
                <input class="input-base" name="name" value="{{ old('name', $status->name) }}" required>
            </div>
            <div>
                <label class="label-base">{{ __('messages.code') }}</label>
                <input class="input-base" name="code" value="{{ old('code', $status->code) }}" required>
            </div>
            <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
                <input type="checkbox" name="is_hadir" value="1" @checked(old('is_hadir', $status->is_hadir))>
                {{ __('messages.total_hadir') }}
            </label>
            <div class="flex gap-2">
                <button class="btn btn-primary">{{ __('messages.save') }}</button>
                <a href="{{ route('program-statuses.index') }}" class="btn btn-outline">{{ __('messages.cancel') }}</a>
            </div>
        </form>
    </div>
</x-app-layout>
