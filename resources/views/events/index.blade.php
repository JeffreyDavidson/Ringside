<x-layouts.app>
    <x-slot name="toolbar">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Events List
        </h2>
    </x-slot>

    <x-content>
        <livewire:events.scheduled-events>
        <livewire:events.unscheduled-events>
        <livewire:events.past-events>
    </x-content>
</x-layouts.app>
