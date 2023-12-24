<x-layouts.app>
    <x-slot name="toolbar">
        <x-toolbar>
            <x-page-heading>Referees List</x-page-heading>
            <x-breadcrumbs.list>
                <x-breadcrumbs.item label="Home" :url="route('dashboard')" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item label="Referees" />
            </x-breadcrumbs.list>
        </x-toolbar>
    </x-slot>

    <livewire:referees.referees-list />
</x-layouts.app>
