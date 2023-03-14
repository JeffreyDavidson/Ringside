<x-toolbar>
    <x-page-heading>Wrestlers List</x-page-heading>
    <x-slot name="breadcrumbs">
        <x-breadcrumbs.list>
            <x-breadcrumbs.item label="Home" :url="route('dashboard')" />
            <x-breadcrumbs.separator />
            <x-breadcrumbs.item label="Wrestlers" />
        </x-breadcrumbs.list>
    </x-slot>
</x-toolbar>
