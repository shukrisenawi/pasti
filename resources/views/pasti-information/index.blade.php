<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold">{{ __('messages.maklumat_pasti') }}</h2>
        </div>
    </x-slot>

    <livewire:pasti-information-index />
</x-app-layout>
