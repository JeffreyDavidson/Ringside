<x-layouts.app>
    <x-slot name="toolbar">
        <x-toolbar>
            <x-page-heading>Edit Venue</x-page-heading>
            <x-breadcrumbs.list>
                <x-breadcrumbs.item :url="route('dashboard')" label="Home" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item :url="route('venues.index')" label="Venues" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item :url="route('venues.show', $venue)" :label="$venue->name" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item label="Edit" />
            </x-breadcrumbs.list>
        </x-toolbar>
    </x-slot>

    <x-card>
        <x-slot name="header">
            <x-card.header title="Edit Venue Form" />
        </x-slot>
        <x-card.body>
            <x-form :action="route('venues.update', $venue)" id="editVenueForm">
                @method('PATCH')
                @include('venues.partials.form')
            </x-form>
        </x-card.body>
        <x-slot name="footer">
            <x-card.footer>
                <x-form.buttons.reset form="editVenueForm"/>
                <x-form.buttons.submit form="editVenueForm"/>
            </x-card.footer>
        </x-slot>
    </x-card>
</x-layouts.app>
