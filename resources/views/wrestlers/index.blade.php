<x-layouts.app>
    <x-slot name="toolbar">
        <x-toolbar>
            <x-page-heading>Wrestlers List</x-page-heading>
            <x-breadcrumbs.list>
                <x-breadcrumbs.item label="Home" :url="route('dashboard')" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item label="Wrestlers" />
            </x-breadcrumbs.list>
        </x-toolbar>
    </x-slot>

    <livewire:wrestlers.wrestlers-list>
</x-layouts.app>
