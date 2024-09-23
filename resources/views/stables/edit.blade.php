<x-layouts.app>
    <x-container-fixed>
        <x-card>
            <x-card.header>
                <x-card.title class="m-0">
                    <x-card.heading>Edit Stable Form</x-card.heading>
                </x-card.title>
            </x-card.header>
            <x-card.body>
                <x-form :action="route('stables.update', $stable)" id="editStableForm">
                    <x-stables.form :$stable :$wrestlers :$tagTeams :$managers />
                </x-form>
            </x-card.body>
            <x-card.footer>
                <x-form.buttons.reset form="editStableForm" />
                <x-form.buttons.submit form="editStableForm" />
            </x-card.footer>
        </x-card>
    </x-container-fixed>
</x-layouts.app>
