<section>
    <header>
        <h2 class="text-lg font-semibold text-slate-900">Kemaskini Maklumat PASTI</h2>

        <p class="mt-1 text-sm text-slate-500">
            Sila lengkapkan maklumat PASTI sebelum meneruskan ke langkah tukar kata laluan.
        </p>
    </header>

    <form method="post" action="{{ route('pasti.self.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('put')

        <div>
            <x-input-label for="pasti_kawasan_id" :value="__('messages.kawasan')" />
            <select id="pasti_kawasan_id" name="kawasan_id" class="input-base mt-1 block w-full" required>
                <option value="">- {{ __('messages.select') }} -</option>
                @foreach(($kawasans ?? collect()) as $kawasan)
                    <option value="{{ $kawasan->id }}" @selected((int) old('kawasan_id', $guruPasti?->kawasan_id) === (int) $kawasan->id)>{{ $kawasan->name }}</option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('kawasan_id')" />
        </div>

        <div>
            <x-input-label for="pasti_name" :value="__('messages.name') . ' ' . __('messages.pasti')" />
            <x-text-input id="pasti_name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $guruPasti?->name)" required />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="pasti_phone" :value="__('messages.phone')" />
            <x-text-input id="pasti_phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone', $guruPasti?->phone)" required />
            <x-input-error class="mt-2" :messages="$errors->get('phone')" />
        </div>

        <div>
            <x-input-label for="pasti_manager_name" :value="__('messages.manager_name')" />
            <x-text-input id="pasti_manager_name" name="manager_name" type="text" class="mt-1 block w-full" :value="old('manager_name', $guruPasti?->manager_name)" required />
            <x-input-error class="mt-2" :messages="$errors->get('manager_name')" />
        </div>

        <div>
            <x-input-label for="pasti_manager_phone" :value="__('messages.manager_phone')" />
            <x-text-input id="pasti_manager_phone" name="manager_phone" type="text" class="mt-1 block w-full" :value="old('manager_phone', $guruPasti?->manager_phone)" required />
            <x-input-error class="mt-2" :messages="$errors->get('manager_phone')" />
        </div>

        <div>
            <x-input-label for="pasti_address" :value="__('messages.address')" />
            <x-text-input id="pasti_address" name="address" type="text" class="mt-1 block w-full" :value="old('address', $guruPasti?->address)" required />
            <x-input-error class="mt-2" :messages="$errors->get('address')" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('messages.save') }}</x-primary-button>
        </div>
    </form>
</section>
