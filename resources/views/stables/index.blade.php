<x-layouts.app>
    <x-slot name="toolbar">
        <x-toolbar>
            <x-page-heading>Stables List</x-page-heading>
            <x-breadcrumbs.list>
                <x-breadcrumbs.item label="Home" :url="route('dashboard')" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item label="Stables" />
            </x-breadcrumbs.list>
        </x-toolbar>
    </x-slot>

    <livewire:stables.stables-list />
</x-layouts.app>
