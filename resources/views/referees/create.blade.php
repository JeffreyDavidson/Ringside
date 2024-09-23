<x-layouts.app>
    <x-container-fixed>
        <x-card>
            <x-card.header>
                <x-card.title class="m-0">
                    <x-card.heading>Create Referee Form</x-card.heading>
                </x-card.title>
            </x-card.header>
            <x-card.body>
                <x-form :action="route('referees.store')" id="createRefereeForm">
                    @include('referees.partials.form')
                </x-form>
            </x-card.body>
            <x-card.footer>
                <x-form.buttons.reset form="createRefereeForm" />
                <x-form.buttons.submit form="createRefereeForm" />
            </x-card.footer>
        </x-card>
    </x-container-fixed>
</x-layouts.app>
