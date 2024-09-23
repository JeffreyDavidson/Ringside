<x-layouts.app>
    <x-container-fixed>
        <x-card>
            <x-card.header>
                <x-card.title class="m-0">
                    <x-card.heading>Create Stable Form</x-card.heading>
                </x-card.title>
            </x-card.header>
            <x-card.body>
                <x-form :action="route('stables.store')" id="createStableForm">
                    <x-stables.form :$stable :$wrestlers :$tagTeams :$managers />
                </x-form>
            </x-card.body>
            <x-card.footer>
                <x-form.buttons.reset form="createStableForm" />
                <x-form.buttons.submit form="createStableForm" />
            </x-card.footer>
        </x-card>
    </x-container-fixed>
</x-layouts.app>
