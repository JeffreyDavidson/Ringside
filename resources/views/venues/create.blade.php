<x-layouts.app>
    <x-container-fixed>
        <x-card>
            <x-card.header>
                <x-card.title class="m-0">
                    <x-card.heading>Create Venue Form</x-card.heading>
                </x-card.title>
            </x-card.header>
            <x-card.body>
                <x-form :action="route('venues.store')" id="createVenueForm">
                    <x-venues.form :$venue :$states />
                </x-form>
            </x-card.body>
            <x-card.footer>
                <x-form.buttons.reset form="createVenueForm" />
                <x-form.buttons.submit form="createVenueForm" />
            </x-card.footer>
        </x-card>
    </x-container-fixed>
</x-layouts.app>
