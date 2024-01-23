<x-layouts.app>
    <x-slot name="toolbar">
        <x-toolbar>
            <x-page-heading>Edit Referee</x-page-heading>
            <x-breadcrumbs.list>
                <x-breadcrumbs.item :url="route('dashboard')" label="Home" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item :url="route('referees.index')" label="Referees" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item :url="route('referees.show', $referee)" :label="$referee->name" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item label="Edit" />
            </x-breadcrumbs.list>
        </x-toolbar>
    </x-slot>

    <x-card>
        <x-slot name="header">
            <x-card.header title="Edit Referee Form" />
        </x-slot>
        <x-card.body>
            <x-form :action="route('referees.update', $referee)">
                @method('PATCH')
                @include('referees.partials.form')
            </x-form>
        </x-card.body>
    </x-card>
</x-layouts.app>
