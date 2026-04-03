<section>
    <header>
        <h2 class="text-lg font-semibold text-slate-900">Pilih PASTI</h2>

        <p class="mt-1 text-sm text-slate-500">
            Sila pilih PASTI yang telah didaftarkan oleh admin sebelum meneruskan ke langkah tukar kata laluan.
        </p>
    </header>

    <form method="post" action="{{ route('profile.pasti-selection.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('put')

        <div>
            <x-input-label for="pasti_id" :value="__('messages.pasti')" />
            <select id="pasti_id" name="pasti_id" class="input-base mt-1 block w-full" required>
                <option value="">- {{ __('messages.select') }} -</option>
                @foreach(($pastis ?? collect()) as $pasti)
                    <option value="{{ $pasti->id }}" @selected((int) old('pasti_id', $guruPastiId) === (int) $pasti->id)>
                        {{ $pasti->name }} ({{ $pasti->kawasan?->name ?? '-' }})
                    </option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('pasti_id')" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('messages.save') }}</x-primary-button>
        </div>
    </form>
</section>
