<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold">{{ __('messages.ajk_program') }}</h2>
        </div>
    </x-slot>

    <livewire:ajk-program-manager />
</x-app-layout>
