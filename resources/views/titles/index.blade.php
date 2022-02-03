<x-layouts.app>
    <x-slot name="toolbar">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Titles List
        </h2>
    </x-slot>

    <x-content>
        <livewire:titles.active-titles>
        <livewire:titles.future-activation-and-unactivated-titles>
        <livewire:titles.inactive-titles>
        <livewire:titles.retired-titles>
    </x-content>
</x-layouts.app>
