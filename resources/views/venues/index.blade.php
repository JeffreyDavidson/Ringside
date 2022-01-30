<x-layouts.app>
    <x-slot name="toolbar">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Venues List
        </h2>
    </x-slot>

    <x-content>
        <div class="card">
             @include('venues.partials.table.header')
             <div class="py-4 card-body">
                <livewire:venues.all-venues>
             </div>
        </div>
    </x-content>
</x-layouts.app>
