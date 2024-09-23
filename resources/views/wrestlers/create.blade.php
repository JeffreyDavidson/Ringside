<x-layouts.app>
    <x-container-fixed>
        <x-card>
            <x-card.header>
                <x-card.title class="m-0">
                    <x-card.heading>Create Wrestler Form</x-card.heading>
                </x-card.title>
            </x-card.header>
            <x-card.body>
                <x-form :action="route('wrestlers.store')" id="createWrestlerForm">
                    @include('wrestlers.partials.form')
                </x-form>
            </x-card.body>
            <x-card.footer>
                <x-form.buttons.reset form="createWrestlerForm" />
                <x-form.buttons.submit form="createWrestlerForm" />
            </x-card.footer>
        </x-card>
    </x-container-fixed>
</x-layouts.app>
