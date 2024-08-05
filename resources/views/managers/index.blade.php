<x-layouts.app>
    <x-slot:toolbar>
        <x-toolbar>
            <x-page-heading>Managers List</x-page-heading>
            <x-breadcrumbs.list>
                <x-breadcrumbs.item label="Home" :url="route('dashboard')" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item label="Managers" />
            </x-breadcrumbs.list>
        </x-toolbar>
    </x-slot>

    <livewire:managers.managers-list />
</x-layouts.app>
