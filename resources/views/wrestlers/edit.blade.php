<x-layouts.app>
    <x-slot name="toolbar">
        <x-toolbar>
            <x-page-heading>Edit Wrestler</x-page-heading>
            <x-breadcrumbs.list>
                <x-breadcrumbs.item :url="route('dashboard')" label="Home" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item :url="route('wrestlers.index')" label="Wrestlers" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item :url="route('wrestlers.show', $wrestler)" :label="$wrestler->name" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item label="Edit" />
            </x-breadcrumbs.list>
        </x-toolbar>
    </x-slot>

    <x-card>
        <x-slot name="header">
            <x-card.header title="Edit Wrestler Form" />
        </x-slot>
        <x-card.body>
            <x-form :action="route('wrestlers.update', $wrestler)">
                @method('PATCH')
                @include('wrestlers.partials.form')
            </x-form>
        </x-card.body>
    </x-card>
</x-layouts.app>
