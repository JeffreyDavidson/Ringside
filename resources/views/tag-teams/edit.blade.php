<x-layouts.app>
    <x-slot name="toolbar">
        <x-toolbar>
            <x-page-heading>Edit Tag Team</x-page-heading>
            <x-breadcrumbs.list>
                <x-breadcrumbs.item :url="route('dashboard')" label="Home" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item :url="route('tag-teams.index')" label="Tag Teams" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item :url="route('tag-teams.show', $tagTeam)" :label="$tagTeam->name" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item label="Edit" />
            </x-breadcrumbs.list>
        </x-toolbar>
    </x-slot>

    <x-card>
        <x-slot name="header">
            <x-card.header title="Edit Tag Team Form" />
        </x-slot>
        <x-card.body>
            <x-form :action="route('tag-teams.update', $tagTeam)">
                @method('PATCH')
                @include('tag-teams.partials.form')
            </x-form>
        </x-card.body>
    </x-card>
</x-layouts.app>
