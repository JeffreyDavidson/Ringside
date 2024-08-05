<x-layouts.app>
    <x-slot:toolbar>
        <x-toolbar>
            <x-page-heading>Deleted Events List</x-page-heading>
            <x-breadcrumbs.list>
                <x-breadcrumbs.item label="Home" :url="route('dashboard')" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item label="Deleted Events" />
            </x-breadcrumbs.list>
        </x-toolbar>
    </x-slot>

    <livewire:events.deleted-events-list />
</x-layouts.app>
