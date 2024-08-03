<x-layouts.app>
    <x-slot name="toolbar">
        <x-toolbar>
            <x-page-heading>Create Venue</x-page-heading>
            <x-breadcrumbs.list>
                <x-breadcrumbs.item :url="route('dashboard')" label="Home" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item :url="route('venues.index')" label="Venues" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item label="Create" />
            </x-breadcrumbs.list>
        </x-toolbar>
    </x-slot>

    <x-card>
        <x-slot name="header">
            <x-card.header title="Create Venue Form" />
        </x-slot>
        <x-card.body>
            <x-form :action="route('venues.store')" id="createVenueForm">
                @include('venues.partials.form')
            </x-form>
        </x-card.body>
        <x-slot name="footer">
            <x-card.footer>
                <x-form.buttons.reset form="createVenueForm"/>
                <x-form.buttons.submit form="createVenueForm"/>
            </x-card.footer>
        </x-slot>
    </x-card>
</x-layouts.app>
