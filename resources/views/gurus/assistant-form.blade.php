<x-app-layout>
    @php
        $formAction = $formAction ?? route('guru-assistants.update', $assistant);
        $cancelRoute = $cancelRoute ?? route('guru-assistants.index', ['tab' => 'list']);
    @endphp

    <x-slot name="header">
        <h2 class="text-lg font-bold">Kemaskini Pembantu Guru</h2>
    </x-slot>

    <div class="card">
        <form method="POST" action="{{ $formAction }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            @method('PUT')

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="label-base">{{ __('messages.name') }}</label>
                    <input class="input-base" name="name" value="{{ old('name', $assistant->name) }}" required>
                </div>
                <div>
                    <label class="label-base">{{ __('messages.pasti') }}</label>
                    @if(isset($pastis))
                        <select class="input-base" name="pasti_id" required>
                            <option value="">-</option>
                            @foreach($pastis as $pasti)
                                <option value="{{ $pasti->id }}" @selected((int) old('pasti_id', $assistant->pasti_id) === $pasti->id)>{{ $pasti->name }}</option>
                            @endforeach
                        </select>
                    @else
                        <input class="input-base" value="{{ $assistant->pasti?->name ?? '-' }}" disabled>
                    @endif
                </div>
                <div>
                    <label class="label-base">{{ __('messages.kad_pengenalan') }}</label>
                    <input class="input-base" name="kad_pengenalan" value="{{ old('kad_pengenalan', $assistant->kad_pengenalan) }}" required data-mask="kad-pengenalan" inputmode="numeric" placeholder="######-##-####">
                </div>
                <div>
                    <label class="label-base">Elaun</label>
                    <input class="input-base" type="number" step="0.01" min="0" name="elaun" value="{{ old('elaun', $assistant->elaun) }}">
                </div>
                <div>
                    <label class="label-base">Elaun Transit</label>
                    <input class="input-base" type="number" step="0.01" min="0" name="elaun_transit" value="{{ old('elaun_transit', $assistant->elaun_transit) }}">
                </div>
                <div>
                    <label class="label-base">Elaun Lain</label>
                    <input class="input-base" type="number" step="0.01" min="0" name="elaun_lain" value="{{ old('elaun_lain', $assistant->elaun_lain) }}">
                </div>
                <div class="md:col-span-2">
                    <label class="label-base">Avatar</label>
                    <div class="mt-2 flex items-center gap-4">
                        <x-avatar :guru="$assistant" size="h-16 w-16" />
                        <div class="w-full">
                            <input type="file" name="avatar" accept=".jpg,.jpeg,.png,.webp,image/*" class="file-input w-full">
                            <p class="mt-1 text-xs text-slate-500">Format: JPG, PNG, WEBP (maks 7MB).</p>
                            <label class="mt-2 inline-flex items-center gap-2 text-sm text-base-content/70">
                                <input type="checkbox" name="remove_avatar" value="1" class="checkbox checkbox-sm" @checked(old('remove_avatar'))>
                                <span>Padam gambar semasa</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex gap-2">
                <button class="btn btn-primary">{{ __('messages.save') }}</button>
                <a href="{{ $cancelRoute }}" class="btn btn-outline">{{ __('messages.cancel') }}</a>
            </div>
        </form>
    </div>
</x-app-layout>
