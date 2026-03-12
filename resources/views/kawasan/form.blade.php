<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-bold">{{ $kawasan->exists ? __('messages.edit') : __('messages.new') }} {{ __('messages.kawasan') }}</h2>
    </x-slot>

    <div class="card">
        <form method="POST" action="{{ $kawasan->exists ? route('kawasan.update', $kawasan) : route('kawasan.store') }}" class="space-y-4">
            @csrf
            @if($kawasan->exists)
                @method('PUT')
            @endif
            <div>
                <label class="label-base">{{ __('messages.name') }}</label>
                <input class="input-base" name="name" value="{{ old('name', $kawasan->name) }}" required>
            </div>
            <div>
                <label class="label-base">{{ __('messages.dun') }}</label>
                <select class="input-base" name="dun" required>
                    <option value="">{{ __('messages.select') }}</option>
                    @foreach($dunOptions as $option)
                        <option value="{{ $option }}" @selected(old('dun', $kawasan->dun) === $option)>{{ $option }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button class="btn btn-primary">{{ __('messages.save') }}</button>
                <a href="{{ route('kawasan.index') }}" class="btn btn-outline">{{ __('messages.cancel') }}</a>
            </div>
        </form>
    </div>
</x-app-layout>
