<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold">{{ __('messages.programs') }}</h2>
        </div>
    </x-slot>

    <livewire:program-index />
</x-app-layout>
