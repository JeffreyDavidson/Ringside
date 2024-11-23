<x-layouts.app>
    <x-container-fluid>
        <x-card>
            <x-card.header>
                <x-card.title class="m-0">
                    <x-card.heading>Edit Event Form</x-card.heading>
                </x-card.title>
            </x-card.header>
            <x-card.body>
                <x-form :action="route('events.store')" id="editEventForm">
                    @method('PATCH')
                    <x-events.form :$event :$venues />
                </x-form>
            </x-card.body>
            <x-card.footer>
                <x-form.buttons.reset form="editEventForm" />
                <x-form.buttons.submit form="editEventForm" />
            </x-card.footer>
        </x-card>
    </x-container-fluid>
</x-layouts.app>
