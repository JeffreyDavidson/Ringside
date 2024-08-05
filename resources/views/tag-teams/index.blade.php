<x-layouts.app>
    <x-slot:toolbar>
        <x-toolbar>
            <x-page-heading>Tag Teams List</x-page-heading>
            <x-breadcrumbs.list>
                <x-breadcrumbs.item label="Home" :url="route('dashboard')" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item label="Tag Teams" />
            </x-breadcrumbs.list>
        </x-toolbar>
    </x-slot>

    <livewire:tag-teams.tag-teams-list />
</x-layouts.app>
