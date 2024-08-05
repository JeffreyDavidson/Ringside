<x-layouts.app>
    <x-slot:toolbar>
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
        <x-card.header>
            <x-card.title class="m-0">
                <x-card.heading>Edit Venue Form</x-card.heading>
            </x-card.title>
        </x-card.header>
        <x-card.body>
            <x-form :action="route('venues.update', $venue)" id="editVenueForm">
                @method('PATCH')
                @include('venues.partials.form')
            </x-form>
        </x-card.body>
        <x-card.footer>
            <x-form.buttons.reset form="editVenueForm"/>
            <x-form.buttons.submit form="editVenueForm"/>
        </x-card.footer>
    </x-card>
</x-layouts.app>
