<x-layouts.app>
    <x-slot:toolbar>
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
        <x-card.header>
            <x-card.title class="m-0">
                <x-card.heading>Create Venue Form</x-card.heading>
            </x-card.title>
        </x-card.header>
        <x-card.body>
            <x-form :action="route('venues.store')" id="createVenueForm">
                @include('venues.partials.form')
            </x-form>
        </x-card.body>
        <x-card.footer>
            <x-form.buttons.reset form="createVenueForm"/>
            <x-form.buttons.submit form="createVenueForm"/>
        </x-card.footer>
    </x-card>
</x-layouts.app>
