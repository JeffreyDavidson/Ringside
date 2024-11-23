<x-layouts.app>
    <x-container-fluid>
        <x-card>
            <x-card.header>
                <x-card.title class="m-0">
                    <x-card.heading>Edit Venue Form</x-card.heading>
                </x-card.title>
            </x-card.header>
            <x-card.body>
                <x-form :action="route('venues.update', $venue)" id="editVenueForm">
                    @method('PATCH')
                    <x-venues.form :$venue :$states />
                </x-form>
            </x-card.body>
            <x-card.footer>
                <x-form.buttons.reset form="editVenueForm" />
                <x-form.buttons.submit form="editVenueForm" />
            </x-card.footer>
        </x-card>
    </x-container-fluid>
</x-layouts.app>
