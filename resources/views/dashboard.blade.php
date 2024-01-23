<x-layouts.app>
    <x-slot name="toolbar">
        <x-toolbar>
            <x-page-heading>Dashboard</x-page-heading>
            <x-breadcrumbs.list>
                <x-breadcrumbs.item :url="route('dashboard')" label="Home" />
            </x-breadcrumbs.list>
        </x-toolbar>
    </x-slot>

    Dashboard Here
</x-layouts.app>
