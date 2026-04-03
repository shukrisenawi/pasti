<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold">{{ __('messages.guru_salary_information') }}</h2>
        </div>
    </x-slot>

    <livewire:guru-salary-information-index />
</x-app-layout>
