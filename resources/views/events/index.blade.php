<x-layouts.app>
    <x-slot name="toolbar">
        <x-toolbar>
            <x-page-heading>Events List</x-page-heading>
            <x-breadcrumbs.list>
                <x-breadcrumbs.item label="Home" :url="route('dashboard')" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item label="Events" />
            </x-breadcrumbs.list>
        </x-toolbar>
    </x-slot>

    <livewire:events.events-list />
</x-layouts.app>
