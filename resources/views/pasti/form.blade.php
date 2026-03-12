<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-bold">{{ $pasti->exists ? __('messages.edit') : __('messages.new') }} {{ __('messages.pasti') }}</h2>
    </x-slot>

    <div class="card">
        <form method="POST" action="{{ ($isOwnUpdate ?? false) ? route('pasti.self.update') : ($pasti->exists ? route('pasti.update', $pasti) : route('pasti.store')) }}" class="grid gap-4 md:grid-cols-2">
            @csrf
            @if($pasti->exists)
                @method('PUT')
            @endif

            <div>
                <label class="label-base">{{ __('messages.kawasan') }}</label>
                <select class="input-base" name="kawasan_id" required>
                    @foreach($kawasans as $kawasan)
                        <option value="{{ $kawasan->id }}" @selected((int) old('kawasan_id', $pasti->kawasan_id) === $kawasan->id)>{{ $kawasan->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label-base">{{ __('messages.name') }}</label>
                <input class="input-base" name="name" value="{{ old('name', $pasti->name) }}" required>
            </div>
            <div>
                <label class="label-base">{{ __('messages.code') }}</label>
                <input class="input-base" name="code" value="{{ old('code', $pasti->code) }}">
            </div>
            <div>
                <label class="label-base">{{ __('messages.phone') }}</label>
                <input class="input-base" name="phone" value="{{ old('phone', $pasti->phone) }}">
            </div>
            <div>
                <label class="label-base">{{ __('messages.manager_name') }}</label>
                <input class="input-base" name="manager_name" value="{{ old('manager_name', $pasti->manager_name) }}">
            </div>
            <div>
                <label class="label-base">{{ __('messages.manager_phone') }}</label>
                <input class="input-base" name="manager_phone" value="{{ old('manager_phone', $pasti->manager_phone) }}">
            </div>
            <div class="md:col-span-2">
                <label class="label-base">{{ __('messages.address') }}</label>
                <input class="input-base" name="address" value="{{ old('address', $pasti->address) }}">
            </div>
            <div class="md:col-span-2 flex gap-2">
                <button class="btn btn-primary">{{ __('messages.save') }}</button>
                <a href="{{ ($isOwnUpdate ?? false) ? route('pasti.self.edit') : route('pasti.index') }}" class="btn btn-outline">{{ __('messages.cancel') }}</a>
            </div>
        </form>
    </div>
</x-app-layout>
