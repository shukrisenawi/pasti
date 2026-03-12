<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-bold">{{ $kelas->exists ? __('messages.edit') : __('messages.new') }} {{ __('messages.kelas') }}</h2>
    </x-slot>

    <div class="card">
        <form method="POST" action="{{ $kelas->exists ? route('kelas.update', $kelas) : route('kelas.store') }}" class="space-y-4">
            @csrf
            @if($kelas->exists)
                @method('PUT')
            @endif

            <div>
                <label class="label-base">{{ __('messages.pasti') }}</label>
                <select class="input-base" name="pasti_id" required>
                    @foreach($pastis as $pasti)
                        <option value="{{ $pasti->id }}" @selected((int) old('pasti_id', $kelas->pasti_id) === $pasti->id)>{{ $pasti->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="label-base">{{ __('messages.name') }}</label>
                <input class="input-base" name="name" value="{{ old('name', $kelas->name) }}" required>
            </div>

            <div class="flex gap-2">
                <button class="btn btn-primary">{{ __('messages.save') }}</button>
                <a href="{{ route('kelas.index') }}" class="btn btn-outline">{{ __('messages.cancel') }}</a>
            </div>
        </form>
    </div>
</x-app-layout>
