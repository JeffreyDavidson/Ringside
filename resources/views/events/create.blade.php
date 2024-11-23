<x-layouts.app>
    <x-container-fluid>
        <x-card>
            <x-card.header>
                <x-card.title class="m-0">
                    <x-card.heading>Create Event Form</x-card.heading>
                </x-card.title>
            </x-card.header>
            <x-card.body>
                <x-form :action="route('events.store')" id="createEventForm">
                    <x-events.form :$event :$venues />
                </x-form>
            </x-card.body>
            <x-card.footer>
                <x-form.buttons.reset form="createEventForm" />
                <x-form.buttons.submit form="createEventForm" />
            </x-card.footer>
        </x-card>
    </x-container-fluid>
</x-layouts.app>
