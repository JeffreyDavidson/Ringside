<x-layouts.app>
    <x-container-fixed>
        <x-card>
            <x-card.header>
                <x-card.title class="m-0">
                    <x-card.heading>Edit Referee Form</x-card.heading>
                </x-card.title>
            </x-card.header>
            <x-card.body>
                <x-form :action="route('referees.update', $referee)" id="editRefereeForm">
                    @method('PATCH')
                    <x-referees.form :$referee />
                </x-form>
            </x-card.body>
            <x-card.footer>
                <x-form.buttons.reset form="editRefereeForm" />
                <x-form.buttons.submit form="editRefereeForm" />
            </x-card.footer>
        </x-card>
    </x-container-fixed>
</x-layouts.app>
