<x-layouts.app>
    <x-container-fixed>
        <x-card>
            <x-card.header>
                <x-card.title class="m-0">
                    <x-card.heading>Create Event Form</x-card.heading>
                </x-card.title>
            </x-card.header>
            <x-card.body>
                <x-form :action="route('events.store')" id="createEventForm">
                    @include('events.partials.form')
                </x-form>
            </x-card.body>
            <x-card.footer>
                <x-form.buttons.reset form="createEventForm" />
                <x-form.buttons.submit form="createEventForm" />
            </x-card.footer>
        </x-card>
    </x-container-fixed>
</x-layouts.app>
