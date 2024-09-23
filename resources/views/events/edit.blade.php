<x-layouts.app>
    <x-container-fixed>
        <x-card>
            <x-card.header>
                <x-card.title class="m-0">
                    <x-card.heading>Edit Event Form</x-card.heading>
                </x-card.title>
            </x-card.header>
            <x-card.body>
                <x-form :action="route('events.store')" id="editEventForm">
                    @method('PATCH')
                    @include('events.partials.form')
                </x-form>
            </x-card.body>
            <x-card.footer>
                <x-form.buttons.reset form="editEventForm" />
                <x-form.buttons.submit form="editEventForm" />
            </x-card.footer>
        </x-card>
    </x-container-fixed>
</x-layouts.app>
