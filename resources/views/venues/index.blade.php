<x-layouts.app>
    <x-slot name="toolbar">
        <x-toolbar>
            <x-page-heading>Venues List</x-page-heading>
            <x-breadcrumbs.list>
                <x-breadcrumbs.item label="Home" :url="route('dashboard')" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item label="Venues" />
            </x-breadcrumbs.list>
        </x-toolbar>
    </x-slot>

    <livewire:venues.venues-list />
</x-layouts.app>
