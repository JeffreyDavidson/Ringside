<x-layouts.app>
    <x-slot:toolbar>
        <x-toolbar>
            <x-page-heading>Titles List</x-page-heading>
            <x-breadcrumbs.list>
                <x-breadcrumbs.item label="Home" :url="route('dashboard')" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item label="Titles" />
            </x-breadcrumbs.list>
        </x-toolbar>
    </x-slot>

    <livewire:titles.titles-list />
</x-layouts.app>
